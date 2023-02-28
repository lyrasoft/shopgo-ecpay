# LYRASOFT ShopGO Ecpay Package

## Installation

Install from composer

```shell
composer require lyrasoft/shopgo-ecpay
```

Register to shopgo config:

```php
use Lyrasoft\ShopGo\Ecpay\EcpayPayment;
use Lyrasoft\ShopGo\Ecpay\EcpayShipping;

return [
    //...

    'shipping' => [
        'types' => [
            // ...
            'ecpay' => EcpayShipping::class, // <-- Add this
        ]
    ],

    'payment' => [
        'types' => [
            // ...
            'ecpay' => EcpayPayment::class, // <-- Add this
        ]
    ],
```

### Session

As ShopGo may need to redirect to outside Payment service to process checkout, you must disable `SameSite` cookie policy
and set `secure` as `TRUE`.

```php
// etc/packages/session.php

return [
    'session' => [
        // ...

        'cookie_params' => [
            // ...
            'secure' => true, // <-- Set this to TRUE
            // ...
            'samesite' => CookiesInterface::SAMESITE_NONE, // Set this to `SAMESITE_NONE`
        ],
```
