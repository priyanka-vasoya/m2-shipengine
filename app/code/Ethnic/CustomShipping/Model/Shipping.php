<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Ethnic\CustomShipping\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class Shipping
{

    public const XML_CONFIG_ENABLE = 'carriers/shipengine/active';
    public const XML_CONFIG_COUNTRY_ID = 'carriers/shipengine/specificcountry';
    public const XML_CONFIG_SANDBOX_KEY = 'carriers/shipengine/sandbox_api_key';
    public const XML_CONFIG_POSTCODE = 'carriers/shipengine/postcode';
    public const XML_CONFIG_CITY = 'carriers/shipengine/city';
    public const XML_CONFIG_STREET_1 = 'carriers/shipengine/street_line1';
    public const XML_CONFIG_STATE = 'carriers/shipengine/state';
    public const XML_CONFIG_BASE_URL = 'carriers/shipengine/base_url';

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        public ScopeConfigInterface $scopeConfig,
    ) {
    }

    /**
     * Enable shipping method
     *
     * @return bool
     */
    public function getEnable(): bool
    {
        return (bool) $this->scopeConfig->getValue(self::XML_CONFIG_ENABLE);
    }

    /**
     * GetCountryId
     *
     * @return string
     */
    public function getCountryId(): string
    {
        return $this->scopeConfig->getValue(self::XML_CONFIG_COUNTRY_ID, ScopeInterface::SCOPE_STORE) ? : '';
    }

    /**
     * GetKey
     *
     * @return string
     */
    public function getAccessKey(): string
    {
        return $this->scopeConfig->getValue(self::XML_CONFIG_SANDBOX_KEY, ScopeInterface::SCOPE_STORE) ? : '';
    }

    /**
     * GetPostCode
     *
     * @return string
     */
    public function getPostCode(): string
    {
        return $this->scopeConfig->getValue(self::XML_CONFIG_POSTCODE, ScopeInterface::SCOPE_STORE) ? :'';
    }

    /**
     * GetCity
     *
     * @return string
     */
    public function getCity(): string
    {
        return $this->scopeConfig->getValue(self::XML_CONFIG_CITY, ScopeInterface::SCOPE_STORE) ? :'';
    }

    /**
     * GetState
     *
     * @return string
     */
    public function getState(): string
    {
        return $this->scopeConfig->getValue(self::XML_CONFIG_STATE, ScopeInterface::SCOPE_STORE) ? :'';
    }

    /**
     * GetStreet
     *
     * @return string
     */
    public function getStreet(): string
    {
        $streetAddress = $this->scopeConfig->getValue(self::XML_CONFIG_STREET_1, ScopeInterface::SCOPE_STORE);
        return $streetAddress;
    }

    /**
     * GetUrl
     *
     * @return string
     */
    public function getBaseUrl(): string
    {
        return $this->scopeConfig->getValue(self::XML_CONFIG_BASE_URL);
    }
}
