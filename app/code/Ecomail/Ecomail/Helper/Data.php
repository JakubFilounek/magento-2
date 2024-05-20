<?php

namespace Ecomail\Ecomail\Helper;

use Ecomail\Ecomail\Model\Config\Source\Address;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\Flag\FlagResource;
use Magento\Framework\FlagFactory;

class Data extends AbstractHelper
{
    const XML_PATH_ECOMAIL_GENERAL_ENABLED = 'ecomail/general/enabled';
    const XML_PATH_ECOMAIL_GENERAL_API_KEY = 'ecomail/general/api_key';
    const XML_PATH_ECOMAIL_GENERAL_SUBSCRIBER_LIST = 'ecomail/general/subscriber_list';
    const XML_PATH_ECOMAIL_GENERAL_SKIP_DOUBLE_OPTIN = 'ecomail/general/skip_double_optin';
    const XML_PATH_ECOMAIL_GENERAL_TRIGGER_AUTORESPONDERS = 'ecomail/general/trigger_autoresponders';

    const XML_PATH_ECOMAIL_PERSONAL_INFORMATION_SEND_NAME = 'ecomail/personal_information/send_name';
    const XML_PATH_ECOMAIL_PERSONAL_INFORMATION_SEND_ADDRESS = 'ecomail/personal_information/send_address';
    const XML_PATH_ECOMAIL_PERSONAL_INFORMATION_ADDRESS_TYPE = 'ecomail/personal_information/address_type';
    const XML_PATH_ECOMAIL_PERSONAL_INFORMATION_SEND_DOB = 'ecomail/personal_information/send_dob';
    const XML_PATH_ECOMAIL_PERSONAL_INFORMATION_SEND_ORDERS = 'ecomail/personal_information/send_orders';
    const XML_PATH_ECOMAIL_PERSONAL_INFORMATION_SEND_CART_ITEMS = 'ecomail/personal_information/send_cart_items';

    const XML_PATH_ECOMAIL_TRACKING_ENABLED = 'ecomail/tracking/enabled';
    const XML_PATH_ECOMAIL_TRACKING_APP_ID = 'ecomail/tracking/app_id';

    /**
     * @param Context $context
     * @param FlagFactory $flagFactory
     * @param FlagResource $flagResource
     */
    public function __construct(
        Context $context,
        FlagFactory $flagFactory,
        FlagResource $flagResource
    ) {
        parent::__construct($context);
        $this->flagFactory = $flagFactory;
        $this->flagResource = $flagResource;
    }

    /**
     * @param null $store
     * @return bool
     */
    public function isEnabled($store = null): bool
    {
        return (bool)$this->scopeConfig->getValue(
            self::XML_PATH_ECOMAIL_GENERAL_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Check if solution is enabled and configured properly.
     *
     * @param null $store
     * @return bool
     */
    public function isAvailable($store = null): bool
    {
        return $this->isEnabled($store) && $this->getSubscriberList($store);
    }

    /**
     * @param null $store
     * @return bool
     */
    public function skipDoubleOptin($store = null): bool
    {
        return (bool)$this->scopeConfig->getValue(
            self::XML_PATH_ECOMAIL_GENERAL_SKIP_DOUBLE_OPTIN,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * @param null $store
     * @return bool
     */
    public function triggerAutoresponders($store = null): bool
    {
        return (bool)$this->scopeConfig->getValue(
            self::XML_PATH_ECOMAIL_GENERAL_TRIGGER_AUTORESPONDERS,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * @param null $store
     * @return string|null
     */
    public function getApiKey($store = null)
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_ECOMAIL_GENERAL_API_KEY,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * @param null $store
     * @return string|null
     */
    public function getSubscriberList($store = null)
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_ECOMAIL_GENERAL_SUBSCRIBER_LIST,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * @param null $store
     * @return bool
     */
    public function sendName($store = null): bool
    {
        return (bool)$this->scopeConfig->getValue(
            self::XML_PATH_ECOMAIL_PERSONAL_INFORMATION_SEND_NAME,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * @param null $store
     * @return bool
     */
    public function sendAddress($store = null): bool
    {
        return (bool)$this->scopeConfig->getValue(
            self::XML_PATH_ECOMAIL_PERSONAL_INFORMATION_SEND_ADDRESS,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * @param null $store
     * @return bool
     */
    public function useShippingAddress($store = null): bool
    {
        return Address::SHIPPING_ADDRESS === (int)$this->scopeConfig->getValue(
            self::XML_PATH_ECOMAIL_PERSONAL_INFORMATION_ADDRESS_TYPE,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * @param null $store
     * @return bool
     */
    public function sendDob($store = null): bool
    {
        return (bool)$this->scopeConfig->getValue(
            self::XML_PATH_ECOMAIL_PERSONAL_INFORMATION_SEND_DOB,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * @param null $store
     * @return bool
     */
    public function sendOrders($store = null): bool
    {
        return (bool)$this->scopeConfig->getValue(
            self::XML_PATH_ECOMAIL_PERSONAL_INFORMATION_SEND_ORDERS,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * @param null $store
     * @return bool
     */
    public function sendCartItems($store = null): bool
    {
        return (bool)$this->scopeConfig->getValue(
            self::XML_PATH_ECOMAIL_PERSONAL_INFORMATION_SEND_CART_ITEMS,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * @param null $store
     * @return bool
     */
    public function isTrackingEnabled($store = null): bool
    {
        return (bool)$this->scopeConfig->getValue(
            self::XML_PATH_ECOMAIL_TRACKING_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * @param null $store
     * @return string|null
     */
    public function getAppId($store = null)
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_ECOMAIL_TRACKING_APP_ID,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * @return string
     */
    public function getWebhookHash()
    {
        $flag = $this->getFlagObject('ecomail_webhook');
        if ($data = $flag->getFlagData()) {
            return $data;
        }

        $value = uniqid();

        $flag->setFlagData($value);
        $this->flagResource->save($flag);
        return $value;
    }

    /**
     * @param string $code
     * @return \Magento\Framework\Flag
     */
    private function getFlagObject($code)
    {
        $flag = $this->flagFactory->create(['data' => ['flag_code' => $code]]);
        $this->flagResource->load(
            $flag,
            $code,
            'flag_code'
        );

        return $flag;
    }
}
