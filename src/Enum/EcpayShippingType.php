<?php

declare(strict_types=1);

namespace Lyrasoft\ShopGo\Ecpay\Enum;

use Windwalker\Utilities\Attributes\Enum\Title;
use Windwalker\Utilities\Contract\LanguageInterface;
use Windwalker\Utilities\Enum\EnumRichInterface;
use Windwalker\Utilities\Enum\EnumRichTrait;

enum EcpayShippingType: string implements EnumRichInterface
{
    use EnumRichTrait;

    #[Title('黑貓')]
    case TCAT = 'TCAT';

    #[Title('中華郵政')]
    case POST = 'POST';

    #[Title('統一超商')]
    case UNIMART = 'UNIMART';

    #[Title('全家超商')]
    case FAMI = 'FAMI';

    #[Title('萊爾富超商')]
    case HILIFE = 'HILIFE';

    #[Title('OK超商')]
    case OKMART = 'OKMART';

    public function trans(LanguageInterface $lang, ...$args): string
    {
        return $lang->trans('app.ecpay.shipping.type.' . $this->name);
    }
}
