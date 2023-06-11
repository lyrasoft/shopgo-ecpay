<?php

/**
 * Part of shopgo project.
 *
 * @copyright  Copyright (C) 2023 __ORGANIZATION__.
 * @license    MIT
 */

declare(strict_types=1);

namespace Lyrasoft\ShopGo\Ecpay;

use Ecpay\Sdk\Services\CheckMacValueService;
use Lyrasoft\ShopGo\Cart\CartData;
use Lyrasoft\ShopGo\Data\ShippingHistory;
use Lyrasoft\ShopGo\Ecpay\Enum\EcpayShippingType;
use Lyrasoft\ShopGo\Entity\Location;
use Lyrasoft\ShopGo\Entity\Order;
use Lyrasoft\ShopGo\Enum\OrderHistoryType;
use Lyrasoft\ShopGo\Field\OrderStateListField;
use Lyrasoft\ShopGo\Field\PaymentModalField;
use Lyrasoft\ShopGo\Service\AddressService;
use Lyrasoft\ShopGo\Service\OrderService;
use Lyrasoft\ShopGo\Shipping\AbstractShipping;
use Lyrasoft\ShopGo\Shipping\PriceRangeTrait;
use Lyrasoft\ShopGo\Shipping\ShipmentCreatingInterface;
use Lyrasoft\ShopGo\Shipping\ShipmentPrintableInterface;
use Lyrasoft\ShopGo\Shipping\ShippingStatusInterface;
use Lyrasoft\ShopGo\Traits\LayoutAwareTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use Unicorn\Field\ButtonRadioField;
use Unicorn\Field\SwitcherField;
use Windwalker\Core\Application\AppContext;
use Windwalker\Core\Application\ApplicationInterface;
use Windwalker\Core\DateTime\ChronosService;
use Windwalker\Core\Form\Exception\ValidateFailException;
use Windwalker\Core\Http\AppRequest;
use Windwalker\Core\Language\LangService;
use Windwalker\Core\Manager\Logger;
use Windwalker\Core\Router\Navigator;
use Windwalker\Core\Router\RouteUri;
use Windwalker\Form\Field\ListField;
use Windwalker\Form\Field\NumberField;
use Windwalker\Form\Field\RadioField;
use Windwalker\Form\Field\SpacerField;
use Windwalker\Form\Field\TextField;
use Windwalker\Form\Form;
use Windwalker\ORM\ORM;
use Windwalker\Utilities\Cache\InstanceCacheTrait;
use Windwalker\Utilities\Str;

use function Windwalker\collect;

/**
 * The EcpayShipping class.
 */
class EcpayShipping extends AbstractShipping implements
    ShipmentCreatingInterface,
    ShipmentPrintableInterface,
    ShippingStatusInterface
{
    use PriceRangeTrait;
    use LayoutAwareTrait;
    use EcpayTrait;
    use InstanceCacheTrait;

    public const TAIWAN_ADDRESS_FORMAT = "{postcode}{state}{city}{address1}{address2}";

    protected static string $type = '';

    public static function getTypeIcon(): string
    {
        return 'fa fa-truck';
    }

    public static function getTypeTitle(LangService $lang): string
    {
        return '綠界物流';
    }

    public static function getTypeDescription(LangService $lang): string
    {
        return '綠界超商取貨與付款';
    }

    public function define(Form $form): void
    {
        $form->ns('params', function (Form $form) {
            $form->fieldset('shipping')
                ->title('物流參數')
                ->register(
                    function (Form $form) {
                        $form->add('gateway', ListField::class)
                            ->label('貨運方式')
                            ->registerFromEnums(EcpayShippingType::class)
                            ->defaultValue(EcpayShippingType::TCAT);

                        $form->add('merchant_id', TextField::class)
                            ->label('MerchantID')
                            ->placeholder($this->getEnvCredentials()[0]);

                        $form->add('hash_key', TextField::class)
                            ->label('HashKey')
                            ->placeholder($this->getEnvCredentials()[1]);

                        $form->add('hash_iv', TextField::class)
                            ->label('HashIV')
                            ->placeholder($this->getEnvCredentials()[2]);

                        $form->add('hr1', SpacerField::class)
                            ->hr(true);

                        $form->add('goods_name', TextField::class)
                            ->label('貨品名稱')
                            ->required(true)
                            ->defaultValue('測試商品');

                        $form->add('sender_name', TextField::class)
                            ->label('寄件人姓名')
                            ->required(true)
                            ->attr('pattern', '^[\u4e00-\u9fa5]{2,5}$|^[a-zA-Z]{4,10}$')
                            ->defaultValue('測試人員');

                        $form->add('sender_phone', TextField::class)
                            ->label('寄件人電話')
                            ->required(true)
                            ->defaultValue('55522345');

                        $form->add('sender_cellphone', TextField::class)
                            ->label('寄件人手機')
                            ->required(true)
                            ->defaultValue('0912345678');

                        $form->add('sender_zipcode', TextField::class)
                            ->label('寄件人郵遞區號')
                            ->required(true)
                            ->defaultValue('106');

                        $form->add('sender_address', TextField::class)
                            ->label('寄件人地址')
                            ->required(true)
                            ->defaultValue('台北市測試地址一段1號1F-1');

                        $form->add('hr2', SpacerField::class)
                            ->hr(true);

                        $form->add('shipping_state', OrderStateListField::class)
                            ->label('配送中狀態')
                            ->defaultValue(13);

                        $form->add('delivered_state', OrderStateListField::class)
                            ->label('已送達狀態')
                            ->defaultValue(2);

                        $form->add('received_state', OrderStateListField::class)
                            ->label('已取貨狀態')
                            ->defaultValue(8);

                        $form->add('unpick_state', OrderStateListField::class)
                            ->label('未取貨狀態')
                            ->defaultValue(16);

                        $form->add('hr2', SpacerField::class)
                            ->hr(true);

                        $form->add('cvs_type', ButtonRadioField::class)
                            ->label('超商合作類型')
                            ->option('店到店 (C2C)', 'C2C')
                            ->option('大宗寄倉 (B2C)', 'B2C')
                            ->defaultValue('B2C')
                            ->help('備註: 統一超商的店到店稱為【統一超商交貨便】');

                        $form->add('is_collection', ButtonRadioField::class)
                            ->label('貨到付款')
                            ->option('是', '1')
                            ->option('否', '0')
                            ->option('選擇特定付款方式', 'listed');

                        $form->add('cod_payments', PaymentModalField::class)
                            ->label('貨到付款對應的付款方式')
                            ->set('showon', ['params/is_collection' => 'listed'])
                            ->multiple(true);

                        $form->add('temperature', ButtonRadioField::class)
                            ->label('溫層')
                            ->option('常溫', '0001')
                            ->option('冷藏', '0002')
                            ->option('冷凍', '0003')
                            ->defaultValue('0001');

                        $form->add('cvs_max_amount', NumberField::class)
                            ->label('超商取貨最大金額')
                            ->min(0)
                            ->defaultValue(19999);

                        $form->add('cvs_min_amount', NumberField::class)
                            ->label('超商取貨最小金額')
                            ->min(0)
                            ->defaultValue(0);
                    }
                );
        });

        $this->registerPricingForm($form);

        $form->ns(
            'params',
            fn(Form $form) => $form->fieldset('layout')
                ->title($this->trans('shopgo.shipping.fieldset.layout'))
                ->register(
                    function (Form $form) {
                        $form->add('checkout_form_layout', TextField::class)
                            ->label($this->trans('shopgo.shipping.field.checkout.form.layout'))
                            ->defaultValue('ecpay-shipping-form');

                        $form->add('orderinfo_layout', TextField::class)
                            ->label($this->trans('shopgo.shipping.field.orderinfo.layout'))
                            ->defaultValue('ecpay-shipping-orderinfo');
                    }
                )
        );
    }

    public function isSupported(CartData $cartData): bool
    {
        $params = $this->getParams();

        if (static::isCVS($params['gateway'] ?? '')) {
            $maxAmount = $params['cvs_max_amount'] ?? 19999;
            $minAmount = $params['cvs_min_amount'] ?? 0;

            $total = $cartData->getTotals()['total'];

            return !($total->gt($maxAmount) || $total->lt($minAmount));
        }

        return true;
    }

    protected function getBasePath(): string
    {
        return __DIR__ . '/../views';
    }

    public function form(Location $location): string
    {
        $layout = $this->getParams()['checkout_form_layout'] ?? null ?: 'ecpay-shipping-form';

        if (!$layout) {
            return '';
        }

        return $this->renderLayout(
            $layout,
            [
                'shipping' => $this,
            ]
        );
    }

    public function prepareOrder(Order $order, CartData $cartData, array $checkoutData = []): Order
    {
        $appRequest = $this->app->service(AppRequest::class);
        $shipping = $checkoutData['shipping'] ?? [];

        if ($this->isCVSType() && $shipping) {
            $data = $order->getShippingData();

            $data['cvsAddress'] = $shipping['CVSAddress'] ?? '';
            $data['cvsOutSide'] = $shipping['CVSOutSide'] ?? '';
            $data['cvsStoreID'] = $shipping['CVSStoreID'] ?? '';
            $data['cvsStoreName'] = $shipping['CVSStoreName'] ?? '';
            $data['cvsTelephone'] = $shipping['CVSTelephone'] ?? '';
            $data['logisticsSubType'] = $shipping['LogisticsSubType'] ?? '';
        }

        return $order;
    }

    public function processCheckout(Order $order, RouteUri $notifyUrl): UriInterface|ResponseInterface|null
    {
        return null;
    }

    public function orderInfo(Order $order): string
    {
        $layout = $this->getParams()['orderinfo_layout'] ?? null ?: 'ecpay-shipping-orderinfo';

        if (!$layout) {
            return '';
        }

        return $this->renderLayout(
            $layout,
            [
                'shipping' => $this,
                'order' => $order,
            ]
        );
    }

    public function runTask(AppContext $app, string $task): mixed
    {
        return match ($task) {
            'mapSelect' => $app->call([$this, 'mapSelect']),
            'mapReply' => $app->call([$this, 'mapReply']),
            'notify' => $app->call([$this, 'notifyStatus']),
        };
    }

    public function mapSelect(AppContext $app, Navigator $nav): string
    {
        $params = $this->getParams();
        $ecpay = $this->getEcpayFactory();
        $callback = $app->input('callback');

        $formService = $ecpay->create('AutoSubmitFormWithCmvService');

        $subType = $this->isTest() ? $this->getGateway() : $this->getSubtype();

        $input = [
            'MerchantID' => $this->getMerchantID(),
            'LogisticsType' => 'CVS',
            'LogisticsSubType' => $subType,

            // 請參考 example/Logistics/Domestic/GetMapResponse.php 範例開發
            'ServerReplyURL' => $reply = (string) $nav->to('shipping_task')
                ->task('mapReply')
                ->id($this->getData()->getId())
                ->var('callback', $callback)
                ->full(),

            'ClientReplyURL' => $reply,
        ];

        $action = $this->getEndpoint('Express/map');

        return $formService->generate($input, $action);
    }

    public function mapReply(AppContext $app): string
    {
        $callback = $app->input('callback');
        $data = json_encode($app->input());

        return <<<HTML
<script>
window.opener.{$callback}($data);
window.close();
</script>
HTML;
    }

    public function notifyStatus(AppContext $app, ORM $orm): string
    {
        Logger::info('shipping-notify', $app->getSystemUri()->full());
        Logger::info('shipping-notify', print_r($_REQUEST, true));

        $orderId = $app->input('order_id');

        try {
            $order = $orm->mustFindOne(Order::class, $orderId);

            $this->updateOrderByShippingStatus($order, (int) $_POST['RtnCode'], $_POST['CVSPaymentNo'] ?? '', $_POST);
        } catch (\Throwable $e) {
            return '0|' . $e->getMessage();
        }

        return '1|OK';
    }

    public function createShipment(Order $order): void
    {
        if ($order->getShippingNo()) {
            return;
        }

        $nav = $this->app->service(Navigator::class);

        $factory = $this->getEcpayFactory(CheckMacValueService::METHOD_MD5);
        $postService = $factory->create('PostWithCmvEncodedStrResponseService');
        $chronosService = $this->app->service(ChronosService::class);

        Logger::info('ecpay-shipment-create', "Create Ecpay shipment for: {$order->getNo()}");

        $no = $order->getNo();

        if ($this->isTest()) {
            $no .= 'T' . OrderService::getCurrentTimeBase62();
        }

        $params = collect($this->getParams());
        $gateway = $params['gateway'];
        $shippingData = $order->getShippingData();

        $name = $shippingData->getName();
        $name = Str::substring($name, 0, 10);

        // Shipping Info
        $shippingInfo = $order->getShippingInfo();
        $shippingInfo->setIsCod($this->isCod($order));
        $shippingInfo->setTradeNo($no);
        $shippingInfo->setTargetName($name);
        $shippingInfo->setAmount((string) $order->getTotal());
        $shippingInfo->setType($this->getSubtype());
        // $shippingInfo->setTargetAddress($no);

        $input = [
            'MerchantID' => $this->getMerchantID(),
            'MerchantTradeNo' => $no,
            'MerchantTradeDate' => $chronosService->toLocalFormat('now', 'Y/m/d H:i:s'),
            'LogisticsType' => $this->getLogisticType(),
            'LogisticsSubType' => $this->getSubtype(),
            'GoodsAmount' => (int) $order->getTotal(),
            'GoodsName' => $params['goods_name'] ?: '測試商品',
            'SenderName' => $params['sender_name'] ?: '測試人員',
            'SenderCellPhone' => $params['sender_mobile'] ?: '0912345678',
            'IsCollection' => $shippingInfo->isCod() ? 'Y' : 'N',
            'ReceiverName' => $name,
            'ReceiverCellPhone' => $shippingData->getMobile() ?: $shippingData->getPhone(),

            'ServerReplyURL' => (string) $nav->to('front::shipping_task')
                ->task('notify')
                ->id($this->getData()->getId()) // Shipping ID
                ->var('order_id', $order->getId())
                ->full(),

            // CVS
            'ReceiverStoreID' => $shippingData->cvsStoreId ?? '',

            // Home
            'SenderZipCode' => $params['sender_zipcode'] ?? '',
            'SenderAddress' => $params['sender_address'] ?? '',
            'ReceiverZipCode' => $shippingData->getPostcode(),
            'ReceiverAddress' => AddressService::format($shippingData, static::TAIWAN_ADDRESS_FORMAT),
        ];

        Logger::info('ecpay-shipment-create', print_r($input, true));

        $endpoint = $this->getEndpoint('Express/Create');
        $res = $postService->post($input, $endpoint);

        if (empty($res['RtnCode'])) {
            $msg = sprintf(
                "建立物流單給 %s 時出現錯誤: %s",
                $order->getNo(),
                array_key_first($res)
            );

            Logger::error('ecpay-shipment-create', $msg);
            throw new ValidateFailException($msg);
        }

        Logger::error('ecpay-shipment-create', 'Receive Data:');
        Logger::error('ecpay-shipment-create', print_r($res, true));

        $order->setShippingNo($res['1|AllPayLogisticsID']);
        $order->setShippingArgs($input);
        $order->setShippingStatus($res['RtnMsg']);

        $shippingData['cvsPaymentNo'] = $res['CVSPaymentNo'];
        $shippingData['cvsValidationNo'] = $res['CVSValidationNo'];
        $shippingData['cvsPrice'] = (int) $order->getTotal();

        $this->orm->updateOne(Order::class, $order);
    }

    public function isCod(Order $order): bool
    {
        $params = $this->getParams();

        $isCollection = $params['is_collection'] ?? '0';

        if ($isCollection === 'listed') {
            return collect($params['cod_payments'] ?? [])
                ->map('intval')
                ->contains($order->getPaymentId());
        }

        return (bool) $isCollection;
    }

    public function updateShippingStatus(Order $order): void
    {
        $factory = $this->getEcpayFactory(CheckMacValueService::METHOD_MD5);

        $postService = $factory->create('PostWithCmvVerifiedEncodedStrResponseService');

        $ecpayShippingNo = $order->getShippingNo();

        if (!$ecpayShippingNo) {
            return;
        }

        $input = [
            'MerchantID' => $this->getMerchantID(),
            'AllPayLogisticsID' => $ecpayShippingNo,
            'TimeStamp' => time(),
        ];

        $response = $postService->post(
            $input,
            $this->getEndpoint('Helper/QueryLogisticsTradeInfo/V3')
        );

        $status = (int) $response['LogisticsStatus'];

        $this->updateOrderByShippingStatus($order, $status, $response['ShipmentNo'] ?? '', $response);
    }

    public function updateOrderByShippingStatus(Order $order, int $status, string $shipmentNo, array $res): void
    {
        $statusText = $this->getStatusText($status);

        if ($statusText === $order->getShippingStatus()) {
            return;
        }

        Logger::info(
            'ecpay-shipping-status',
            "Update shipping status for: {$order->getNo()} => {$status}"
        );

        Logger::info(
            'ecpay-shipping-status',
            print_r($res, true)
        );

        $order->setShippingStatus($statusText);
        $order->getShippingInfo()
            ->setShipmentNo($shipmentNo ?? '')
            ->setStatus((string) $status)
            ->setStatusText($statusText);
        $histories = $order->getShippingHistory();
        $histories[] = (new ShippingHistory())
            ->setTime('now')
            ->setStatusText($statusText)
            ->setStatusCode((string) $status);

        $params = collect($this->getParams());

        $this->orm->updateOne(Order::class, $order);

        $orderService = $this->app->service(OrderService::class);

        if ($this->isReceivedStatus($this->getSubtype(), $status)) {
            $orderService->transition(
                $order,
                (int) $params['received_state'],
                OrderHistoryType::SYSTEM(),
                $statusText
            );
        } elseif ($this->isShippingStatus($this->getSubtype(), $status)) {
            $order->setShippedAt('now');
            $this->orm->updateOne(Order::class, $order);

            $orderService->transition(
                $order,
                (int) $params['shipping_state'],
                OrderHistoryType::SYSTEM(),
                $statusText
            );
        } elseif ($this->isDeliveredStatus($this->getSubtype(), $status)) {
            $orderService->transition(
                $order,
                (int) $params['delivered_state'],
                OrderHistoryType::SYSTEM(),
                $statusText
            );
        } elseif ($this->isUnPickStatus($this->getSubtype(), $status)) {
            $orderService->transition(
                $order,
                (int) $params['unpick_state'],
                OrderHistoryType::SYSTEM(),
                $statusText
            );
        }
    }

    public function isDeliveredStatus(string $subType, int $status): bool
    {
        return match ($subType) {
            'UNIMART', 'UNIMARTC2C' => $status === 2073,
            'FAMI', 'FAMIC2C' => $status === 3018,
            'HILIFE', 'HILIFEC2C' => $status === 2063 || $status === 3018,
            default => false
        };
    }

    public function isReceivedStatus(string $subType, int $status): bool
    {
        return match ($subType) {
            'UNIMART', 'UNIMARTC2C' => $status === 2067,
            'FAMI', 'FAMIC2C' => $status === 3022,
            'HILIFE', 'HILIFEC2C' => $status === 2067 || $status === 3022,
            default => false
        };
    }

    public function isShippingStatus(string $subType, int $status): bool
    {
        return match ($subType) {
            'UNIMART', 'UNIMARTC2C' => $status === 2068,
            'FAMI', 'FAMIC2C' => $status === 3032,
            'HILIFE', 'HILIFEC2C' => $status === 2030 || $status === 3032,
            default => false
        };
    }

    public function isUnPickStatus(string $subType, int $status): bool
    {
        return match ($subType) {
            'UNIMART', 'UNIMARTC2C' => $status === 2074,
            'FAMI', 'FAMIC2C' => $status === 3020,
            'HILIFE', 'HILIFEC2C' => $status === 2074 || $status === 3020,
            default => false
        };
    }

    public function getStatusText(int $status): string
    {
        $type = $this->getLogisticType();
        $subType = $this->getSubtype();

        $statusMap = $this->cacheStorage['statuses']
            ??= include __DIR__ . '/shipping_status.php';

        $text = $statusMap[$type][$subType][$status] ?? null;

        if ($text !== null) {
            return $text;
        }

        $subType = match ($subType) {
            'FAMIC2C' => 'FAMI',
            'HILIFEC2C' => 'HILIFE',
            'UNIMARTC2C' => 'UNIMART',
            default => $subType
        };

        return $statusMap[$type][$subType][$status] ?? '未知狀態';
    }

    /**
     * Batch print multiple shipments.
     *
     * @param  ApplicationInterface  $app
     * @param  iterable<Order>       $orders
     *
     * @return  mixed Return response, uri or text.
     */
    public function printShipments(ApplicationInterface $app, iterable $orders): mixed
    {
        $params = $this->getParams();
        $gateway = $this->getLogisticType();
        $subType = $this->getSubtype();

        $b2c = !str_contains($subType, 'C2C');
        $logisticIds = [];
        $paymentNos = [];
        $validationNos = [];

        $autoSubmitFormService = $this->getEcpayFactory(CheckMacValueService::METHOD_MD5)
            ->create('AutoSubmitFormWithCmvService');

        foreach ($orders as $order) {
            if (!$order->getShippingNo()) {
                $this->createShipment($order);
            }

            $shippingData  = $order->getShippingData();
            $logisticIds[] = $order->getShippingNo();

            // Todo: Use camel after ValueObject issue fixed: https://github.com/windwalker-io/framework/issues/1046
            $paymentNos[] = $shippingData->cvsPaymentNo ?? $shippingData->CVSPaymentNo ?? '';
            $validationNos[] = $shippingData->cvsValidationNo ?? $shippingData->CVSValidationNo ?? '';
        }

        $input = [
            'MerchantID' => $this->getMerchantID(),
            'AllPayLogisticsID' => implode(',', $logisticIds),
            'CVSPaymentNo' => implode(',', $paymentNos),
            'CVSValidationNo' => implode(',', $validationNos),
        ];

        if ($gateway !== 'HOME' && !$b2c) {
            $uri = match ($this->getSubtype()) {
                'UNIMARTC2C' => 'Express/PrintUniMartC2COrderInfo',
                'FAMIC2C' => 'Express/PrintFAMIC2COrderInfo',
                'HILIFEC2C' => 'Express/PrintHILIFEC2COrderInfo',
                'OKMARTC2C' => 'Express/PrintOKMARTC2COrderInfo',
            };
        } else {
            $uri = 'helper/printTradeDocument';
        }

        return $autoSubmitFormService->generate($input, $this->getEndpoint($uri));
    }

    public function getEndpoint(string $path): string
    {
        $stage = $this->isTest() ? '-stage' : '';

        return "https://logistics{$stage}.ecpay.com.tw/" . $path;
    }

    public function getGateway(): string
    {
        $params = $this->getParams();

        return $params['gateway'] ?? '';
    }

    public function getLogisticType(): string
    {
        return $this->isCVSType() ? 'CVS' : 'HOME';
    }

    public function getSubtype()
    {
        $params = $this->getParams();
        $gateway = $params['gateway'] ?? '';

        if ($params['cvs_type'] === 'C2C') {
            return $gateway . 'C2C';
        }

        return $gateway;
    }

    public function isCVSType(): bool
    {
        $params = $this->getParams();

        $type = $params['gateway'] ?? '';

        return static::isCVS($type);
    }

    public static function isCVS(string $type): bool
    {
        return in_array($type, ['FAMI', 'UNIMART', 'HILIFE'], true);
    }


    public function getEcpayServiceName(): string
    {
        return 'shipping';
    }
}
