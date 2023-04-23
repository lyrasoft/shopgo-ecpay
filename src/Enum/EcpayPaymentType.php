<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    MIT
 */

declare(strict_types=1);

namespace Lyrasoft\ShopGo\Ecpay\Enum;

use Windwalker\Utilities\Attributes\Enum\Title;
use Windwalker\Utilities\Contract\LanguageInterface;
use Windwalker\Utilities\Enum\EnumSingleton;
use Windwalker\Utilities\Enum\EnumTranslatableInterface;
use Windwalker\Utilities\Enum\EnumTranslatableTrait;

/**
 * The EcpayPaymentType enum class.
 *
 * @method static $this ATM()
 * @method static $this BARCODE()
 * @method static $this CVS()
 * @method static $this WEBATM()
 * @method static $this APPLE_PAY()
 * @method static $this CREDIT()
 */
class EcpayPaymentType extends EnumSingleton implements EnumTranslatableInterface
{
    use EnumTranslatableTrait;

    #[Title('ATM 虛擬帳戶繳款')]
    public const ATM = 'ATM';

    #[Title('超商條碼繳款')]
    public const BARCODE = 'BARCODE';

    #[Title('CVS 超商代碼繳款')]
    public const CVS = 'CVS';

    #[Title('WebATM 繳款')]
    public const WEBATM = 'WebATM';

    #[Title('Apple Pay')]
    public const APPLE_PAY = 'ApplePay';

    #[Title('信用卡繳款')]
    public const CREDIT = 'Credit';

    /**
     * Unable to directly new this object.
     *
     * @param  mixed  $value
     *
     * @throws \UnexpectedValueException if incompatible type is given.
     */
    protected function __construct(mixed $value)
    {
        parent::__construct($value);
    }

    public function trans(LanguageInterface $lang, ...$args): string
    {
        return $lang->trans('app.ecpay.payment.type.' . $this->getKey());
    }
}
