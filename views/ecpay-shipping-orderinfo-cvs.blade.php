<?php

declare(strict_types=1);

namespace App\view;

/**
 * Global variables
 * --------------------------------------------------------------
 * @var $app       AppContext      Application context.
 * @var $vm        object          The view model object.
 * @var $uri       SystemUri       System Uri information.
 * @var $chronos   ChronosService  The chronos datetime service.
 * @var $nav       Navigator       Navigator object to build route.
 * @var $asset     AssetService    The Asset manage service.
 * @var $lang      LangService     The language translation service.
 */

use Lyrasoft\ShopGo\Ecpay\EcpayShipping;
use Lyrasoft\ShopGo\Entity\Order;
use Windwalker\Core\Application\AppContext;
use Windwalker\Core\Asset\AssetService;
use Windwalker\Core\DateTime\ChronosService;
use Windwalker\Core\Language\LangService;
use Windwalker\Core\Router\Navigator;
use Windwalker\Core\Router\SystemUri;

use function Windwalker\uid;

/**
 * @var $shipping EcpayShipping
 * @var $order    Order
 */

$shippingData = $order->shippingData;
$shippingInfo = $order->shippingInfo;

?>

<dl class="row mb-0">
    <dt class="col-lg-3">
        門市名稱
    </dt>

    <dd class="col-lg-9">
        {{ $shippingData->CvsStoreName }}
    </dd>

    <dt class="col-lg-3">
        門市地址
    </dt>

    <dd class="col-lg-9">
        <a href="https://www.google.com/maps/place/{{ $shippingData->CvsAddress }}" target="_blank">
            {{ $shippingData->CvsAddress }}
            <i class="fa fa-external-link"></i>
        </a>
    </dd>

    <dt class="col-lg-3">
        門市店號
    </dt>

    <dd class="col-lg-9">
        {{ $shippingData->CvsStoreId }}
    </dd>

    <dt class="col-lg-3">
        配送單號
    </dt>

    <dd class="col-lg-9">
        {{ $shippingInfo->shipmentNo ?: '-' }}
    </dd>
</dl>
