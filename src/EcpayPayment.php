<?php

/**
 * Part of shopgo project.
 *
 * @copyright  Copyright (C) 2023 __ORGANIZATION__.
 * @license    MIT
 */

declare(strict_types=1);

namespace Lyrasoft\ShopGo\Ecpay;

use Ecpay\Sdk\Response\VerifiedArrayResponse;
use Ecpay\Sdk\Services\CheckMacValueService;
use Ecpay\Sdk\Services\UrlService;
use Lyrasoft\ShopGo\Cart\CartData;
use Lyrasoft\ShopGo\Cart\CartStorage;
use Lyrasoft\ShopGo\Ecpay\Enum\EcpayPaymentType;
use Lyrasoft\ShopGo\Entity\Location;
use Lyrasoft\ShopGo\Entity\Order;
use Lyrasoft\ShopGo\Entity\OrderItem;
use Lyrasoft\ShopGo\Enum\OrderHistoryType;
use Lyrasoft\ShopGo\Field\OrderStateListField;
use Lyrasoft\ShopGo\Payment\AbstractPayment;
use Lyrasoft\ShopGo\Service\OrderService;
use Lyrasoft\ShopGo\Traits\LayoutAwareTrait;
use Windwalker\Core\Application\AppContext;
use Windwalker\Core\DateTime\ChronosService;
use Windwalker\Core\Language\LangService;
use Windwalker\Core\Language\TranslatorTrait;
use Windwalker\Core\Manager\Logger;
use Windwalker\Core\Router\Navigator;
use Windwalker\Core\Router\RouteUri;
use Windwalker\Form\Field\CheckboxesField;
use Windwalker\Form\Field\ListField;
use Windwalker\Form\Field\SpacerField;
use Windwalker\Form\Field\TextField;
use Windwalker\Form\Form;
use Windwalker\ORM\ORM;

use function Windwalker\chronos;

/**
 * The EcpayPayment class.
 */
class EcpayPayment extends AbstractPayment
{
    use TranslatorTrait;
    use LayoutAwareTrait;
    use EcpayTrait;

    protected static string $type = '';

    public static function getTypeIcon(): string
    {
        return 'fa fa-money-bill-1-wave';
    }

    public static function getTypeTitle(LangService $lang): string
    {
        return '綠界支付';
    }

    public static function getTypeDescription(LangService $lang): string
    {
        return '綠界金流整合功能';
    }

    public function define(Form $form): void
    {
        $form->ns('params', function (Form $form) {
            $form->fieldset('payment')
                ->title('支付參數')
                ->register(
                    function (Form $form) {
                        $form->add('merchant_id', TextField::class)
                            ->label('MerchantID')
                            ->placeholder($this->getEnvCredentials()[0]);

                        $form->add('hash_key', TextField::class)
                            ->label('HashKey')
                            ->placeholder($this->getEnvCredentials()[1]);

                        $form->add('hash_iv', TextField::class)
                            ->label('HashIV')
                            ->placeholder($this->getEnvCredentials()[2]);

                        $form->add('gateway', ListField::class)
                            ->label('支付方式')
                            ->registerFromEnums(EcpayPaymentType::class)
                            ->defaultValue(EcpayPaymentType::ATM);

                        $form->add('installment', CheckboxesField::class)
                            ->label('信用卡分期')
                            ->option('三期', '3')
                            ->option('六期', '6')
                            ->option('十二期', '12')
                            ->option('十八期', '18')
                            ->option('二十四期', '24')
                            ->set('showon', ['params/gateway' => 'Credit'])
                            ->wrapperAttr('data-novalidate', true);

                        $form->add('hr1', SpacerField::class)
                            ->hr(true);

                        $form->add('paid_state', OrderStateListField::class)
                            ->label('付款後狀態')
                            ->defaultValue(5);

                        $form->add('failure_state', OrderStateListField::class)
                            ->label('失敗狀態')
                            ->defaultValue(4);
                    }
                );
        });

        $form->ns(
            'params',
            fn (Form $form) => $form->fieldset('layout')
                ->title($this->trans('shopgo.payment.fieldset.layout'))
                ->register(
                    function (Form $form) {
                        $form->add('checkout_form_inject_id', TextField::class)
                            ->label($this->trans('shopgo.payment.field.checkout.form.layout'))
                            ->defaultValue('ecpay-payment-form');
                    }
                )
        );
    }

    protected function getBasePath(): string
    {
        return __DIR__ . '/../views';
    }


    public function form(Location $location): ?array
    {
        // Todo: make installment choose layout
        $layout = $this->getParams()['checkout_form_inject_id'] ?? null ?: 'shopgo.ecpay.payment-form';

        return null;
    }

    public function prepareOrder(Order $order, CartData $cartData, array $checkoutData = []): Order
    {
        return $order;
    }

    public function processCheckout(Order $order, RouteUri $completeUrl): string
    {
        $params = $this->getParams();

        $nav = $this->app->service(Navigator::class);
        $chronos = $this->app->service(ChronosService::class);
        $expireDate = chronos('+10minutes');

        $notify = $nav->to('payment_task')
            ->id($this->getData()->id)
            ->var('no', $order->no)
            ->full();

        $desc = [];

        /** @var OrderItem $orderItem */
        foreach ($order->orderItems->getAttachedEntities() as $orderItem) {
            $desc[] = "{$orderItem->title} x {$orderItem->quantity}";
        }

        $input = [
            'MerchantID' => $this->getMerchantID(),
            'MerchantTradeNo' => $order->paymentNo,
            'MerchantTradeDate' => $chronos->toLocalFormat('now', 'Y/m/d H:i:s'),
            'PaymentType' => 'aio',
            'TotalAmount' => (int) $order->total,
            'TradeDesc' => UrlService::ecpayUrlEncode('Shop Checkout'),
            'ItemName' => implode("#", $desc),
            'ReturnURL' => (string) $notify->task('receivePaid'),
            'ClientBackURL' => (string) $notify->task('return'),
            'ChoosePayment' => $params['gateway'],
            'EncryptType' => 1,

            'ExpireDate' => 7,
            'PaymentInfoURL' => (string) $notify->task('paymentInfo'),
        ];

        if ($params['gateway'] === 'Credit') {
            if ($params['installment']) {
                $input['CreditInstallment'] = implode(',', (array) $params['installment']);
            }
        }

        if ($params['gateway'] === 'ATM') {
            $expireDate = chronos('+7days');
        }

        $factory = $this->getEcpayFactory();
        $autoSubmitFormService = $factory->create('AutoSubmitFormWithCmvService');

        $orm = $this->app->service(ORM::class);

        $orm->updateBatch(
            Order::class,
            [
                'expiry_on' => $expireDate,
                'payment_args' => json_encode($input)
            ],
            ['id' => $order->id]
        );

        return $autoSubmitFormService->generate(
            $input,
            $this->getEndpoint('Cashier/AioCheckOut/V5')
        );
    }

    public function orderInfo(Order $order): string
    {
        return '';
    }

    public function runTask(AppContext $app, string $task): mixed
    {
        return match ($task) {
            'receivePaid', 'return', 'paymentInfo' => $app->call([$this, $task])
        };
    }

    public function receivePaid(AppContext $app): string
    {
        $factory = $this->getEcpayFactory();
        /** @var VerifiedArrayResponse $checkoutResponse */
        $checkoutResponse = $factory->create(VerifiedArrayResponse::class);

        $no = (string) $app->input('no');

        try {
            $res = $checkoutResponse->get($_POST);
        } catch (\Exception $e) {
            Logger::error('ecpay-payment-error', $no);
            Logger::error('ecpay-payment-error', $e->getMessage());
            return '0|' . $e->getMessage();
        }

        $orm = $app->service(ORM::class);
        $orderService = $app->service(OrderService::class);

        $order = $orm->findOne(Order::class, compact('no'));

        if (!$order) {
            Logger::error('ecpay-payment-error', $no);
            Logger::error('ecpay-payment-error', 'Order not found');
            return '0|No order';
        }

        $params = &$order->params;
        $params['payment_notify_error'] = null;

        try {
            if ((string) $res['RtnCode'] === '1') {
                $paidStateId = (int) $this->getParams()['paid_state'];

                if (!$order->paidAt) {
                    $order->paidAt = 'now';
                }

                $orderService->transition(
                    $order,
                    $paidStateId,
                    OrderHistoryType::SYSTEM,
                    '付款成功',
                    true
                );
            } else {
                $failedStateId = (int) $this->getParams()['failure_state'];

                $orderService->transition(
                    $order,
                    $failedStateId,
                    OrderHistoryType::SYSTEM,
                    $res['RtnMsg'] ?? '付款失敗',
                    false
                );
            }
        } catch (\Throwable $e) {
            $params['payment_notify_error'] = $e->getMessage();

            $orm->updateBatch(
                Order::class,
                compact('params'),
                ['id' => $order->id]
            );

            Logger::error('ecpay-payment-error', $no);
            Logger::error('ecpay-payment-error', $e->getMessage());

            return '0|' . $e->getMessage();
        }

        return '1|OK';
    }

    public function return(AppContext $app, Navigator $nav, CartStorage $cartStorage): RouteUri
    {
        $no = (string) $app->input('no');

        $cartStorage->clearChecked();
        $app->state->forget('checkout.data');

        return $nav->to('checkout')
            ->layout('complete')
            ->var('no', $no);
    }

    public function paymentInfo(AppContext $app): string
    {
        $orm = $app->service(ORM::class);

        $no = (string) $app->input('no');

        $order = $orm->mustFindOne(Order::class, compact('no'));

        $order->paymentInfo = $app->input()->dump();

        $orm->updateOne(Order::class, $order);

        return '1|OK';
    }

    public function getEndpoint(string $path): string
    {
        $stage = $this->isTest() ? '-stage' : '';

        return "https://payment{$stage}.ecpay.com.tw/" . $path;
    }

    public function getEcpayServiceName(): string
    {
        return 'payment';
    }
}
