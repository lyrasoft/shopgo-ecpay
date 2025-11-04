<?php

declare(strict_types=1);

namespace Lyrasoft\ShopGo\Ecpay\Enum;

use Windwalker\Utilities\Attributes\Enum\Title;
use Windwalker\Utilities\Contract\LanguageInterface;
use Windwalker\Utilities\Enum\EnumRichInterface;
use Windwalker\Utilities\Enum\EnumRichTrait;

enum EcpayPaymentType: string implements EnumRichInterface
{
    use EnumRichTrait;

    #[Title('ATM 虛擬帳戶繳款')]
    case ATM = 'ATM';

    #[Title('超商條碼繳款')]
    case BARCODE = 'BARCODE';

    #[Title('CVS 超商代碼繳款')]
    case CVS = 'CVS';

    #[Title('WebATM 繳款')]
    case WEBATM = 'WebATM';

    #[Title('Apple Pay')]
    case APPLE_PAY = 'ApplePay';

    #[Title('信用卡繳款')]
    case CREDIT = 'Credit';

    public function trans(LanguageInterface $lang, ...$args): string
    {
        return $lang->trans('app.ecpay.payment.type.' . $this->name);
    }
}
