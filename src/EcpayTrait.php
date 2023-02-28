<?php

/**
 * Part of shopgo project.
 *
 * @copyright  Copyright (C) 2023 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Lyrasoft\ShopGo\Ecpay;

use Ecpay\Sdk\Factories\Factory;

/**
 * Trait EcpayTrait
 */
trait EcpayTrait
{
    abstract public function getEndpoint(string $path): string;

    abstract public function getEcpayServiceName(): string;

    public function isTest(): bool
    {
        return $this->getMerchantID() === '2000132';
    }

    public function getMerchantID(): string
    {
        return $this->getParams()['merchant_id'] ?: $this->getEnvCredentials()[0];
    }

    public function getHashKey(): string
    {
        return $this->getParams()['hash_key'] ?: $this->getEnvCredentials()[1];
    }

    public function getHashIV(): string
    {
        return $this->getParams()['hash_iv'] ?: $this->getEnvCredentials()[2];
    }

    public function getEcpay(): Factory
    {
        $params = $this->getParams();

        return new Factory(
            [
                'hashKey' => $this->getHashKey(),
                'hashIv' => $this->getHashIV(),
            ]
        );
    }

    /**
     * @return  string[]
     */
    protected function getEnvCredentials(): array
    {
        $serviceName = strtolower(static::getEcpayServiceName());

        return [
            env("ECPAY_{$serviceName}_MERCHANT_ID", '2000132'),
            env("ECPAY_{$serviceName}_HASH_KEY", '5294y06JbISpM5x9'),
            env("ECPAY_{$serviceName}_HASH_IV", 'v77hoKGq4kWxNNIS')
        ];
    }
}
