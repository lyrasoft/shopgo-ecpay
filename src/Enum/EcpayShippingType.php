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
 * The EcpayShippingType enum class.
 *
 * @method static $this TCAT()
 * @method static $this POST()
 * @method static $this UNIMART()
 * @method static $this FAMI()
 * @method static $this HILIFE()
 * @method static $this OKMART()
 */
class EcpayShippingType extends EnumSingleton implements EnumTranslatableInterface
{
    use EnumTranslatableTrait;

    #[Title('黑貓')]
    public const TCAT = 'TCAT';

    #[Title('中華郵政')]
    public const POST = 'POST';

    #[Title('統一超商')]
    public const UNIMART = 'UNIMART';

    #[Title('全家超商')]
    public const FAMI = 'FAMI';

    #[Title('萊爾富超商')]
    public const HILIFE = 'HILIFE';

    #[Title('OK超商')]
    public const OKMART = 'OKMART';

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
        return $lang->trans('app.ecpay.shipping.type.' . $this->getKey());
    }
}
