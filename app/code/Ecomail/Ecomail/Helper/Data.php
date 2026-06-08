<?php

namespace Ecomail\Ecomail\Helper;

use Ecomail\Ecomail\Model\Config\Source\Address;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Store\Model\ScopeInterface;

class Data extends AbstractHelper
{
    const XML_PATH_ECOMAIL_GENERAL_ENABLED = 'ecomail/general/enabled';
    const XML_PATH_ECOMAIL_GENERAL_API_KEY = 'ecomail/general/api_key';
    const XML_PATH_ECOMAIL_GENERAL_SUBSCRIBER_LIST = 'ecomail/general/subscriber_list';
    const XML_PATH_ECOMAIL_GENERAL_SKIP_DOUBLE_OPTIN = 'ecomail/general/skip_double_optin';
    const XML_PATH_ECOMAIL_GENERAL_TRIGGER_AUTORESPONDERS = 'ecomail/general/trigger_autoresponders';
    const XML_PATH_ECOMAIL_GENERAL_SUBSCRIBER_SOURCE = 'ecomail/general/subscriber_source';
    const XML_PATH_ECOMAIL_GENERAL_WEBHOOK_TOKEN = 'ecomail/general/webhook_token';
    const XML_PATH_ECOMAIL_GENERAL_SYNC_EXISTING = 'ecomail/general/sync_existing';
    const XML_PATH_ECOMAIL_GENERAL_SYNC_INCLUDE_TAGS = 'ecomail/general/sync_include_tags';
    const XML_PATH_ECOMAIL_GENERAL_SYNC_UPDATE_EXISTING = 'ecomail/general/sync_update_existing';
    const XML_PATH_ECOMAIL_GENERAL_SYNC_CUSTOMER_BATCH_SIZE = 'ecomail/general/sync_customer_batch_size';
    const XML_PATH_ECOMAIL_GENERAL_SYNC_ORDER_BATCH_SIZE = 'ecomail/general/sync_order_batch_size';
    const XML_PATH_ECOMAIL_GENERAL_CHECKOUT_OPT_OUT_LABEL = 'ecomail/general/checkout_opt_out_label';

    const XML_PATH_ECOMAIL_PERSONAL_INFORMATION_SEND_NAME = 'ecomail/personal_information/send_name';
    const XML_PATH_ECOMAIL_PERSONAL_INFORMATION_SEND_ADDRESS = 'ecomail/personal_information/send_address';
    const XML_PATH_ECOMAIL_PERSONAL_INFORMATION_ADDRESS_TYPE = 'ecomail/personal_information/address_type';
    const XML_PATH_ECOMAIL_PERSONAL_INFORMATION_SEND_DOB = 'ecomail/personal_information/send_dob';
    const XML_PATH_ECOMAIL_PERSONAL_INFORMATION_SEND_ORDER_TRANSACTIONS = 'ecomail/personal_information/send_orders';
    const XML_PATH_ECOMAIL_PERSONAL_INFORMATION_UPDATE_CONTACTS_FROM_ORDERS = 'ecomail/personal_information/update_contacts_from_orders';
    const XML_PATH_ECOMAIL_PERSONAL_INFORMATION_SEND_CART_ITEMS = 'ecomail/personal_information/send_cart_items';
    const XML_PATH_ECOMAIL_PERSONAL_INFORMATION_SEND_GROUPS = 'ecomail/personal_information/send_groups';
    const XML_PATH_ECOMAIL_PERSONAL_INFORMATION_SEND_LOCALE = 'ecomail/personal_information/send_locale';

    const XML_PATH_ECOMAIL_TRACKING_ENABLED = 'ecomail/tracking/enabled';
    const XML_PATH_ECOMAIL_TRACKING_RESPECT_COOKIE_CONSENT = 'ecomail/tracking/respect_cookie_consent';
    const XML_PATH_ECOMAIL_TRACKING_APP_ID = 'ecomail/tracking/app_id';
    const XML_PATH_ECOMAIL_TRACKING_PRODUCT_VIEW = 'ecomail/tracking/product_view';
    const XML_PATH_ECOMAIL_TRACKING_FORM_ENABLED = 'ecomail/tracking/form_enabled';
    const XML_PATH_ECOMAIL_TRACKING_FORM_ID = 'ecomail/tracking/form_id';
    const XML_PATH_ECOMAIL_TRACKING_FORM_ACCOUNT = 'ecomail/tracking/form_account';
    const XML_PATH_MAGENTO_COOKIE_RESTRICTION = 'web/cookie/cookie_restriction';

    /**
     * @var EncryptorInterface
     */
    private $encryptor;

    /**
     * @param Context $context
     * @param EncryptorInterface $encryptor
     */
    public function __construct(
        Context $context,
        EncryptorInterface $encryptor
    ) {
        parent::__construct($context);
        $this->encryptor = $encryptor;
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
        return $this->isEnabled($store) && $this->getApiKey($store) && $this->getSubscriberList($store);
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
     * @return string
     */
    public function getSubscriberSource($store = null): string
    {
        $source = (string)$this->scopeConfig->getValue(
            self::XML_PATH_ECOMAIL_GENERAL_SUBSCRIBER_SOURCE,
            ScopeInterface::SCOPE_STORE,
            $store
        );

        $source = trim((string)preg_replace('/[\x00-\x1F\x7F]/', '', $source));

        if ($source === '') {
            $source = 'magento_plugin';
        }

        return substr($source, 0, 64);
    }

    /**
     * @param null $store
     * @return string|null
     */
    public function getApiKey($store = null)
    {
        $value = $this->scopeConfig->getValue(
            self::XML_PATH_ECOMAIL_GENERAL_API_KEY,
            ScopeInterface::SCOPE_STORE,
            $store
        );

        if (!$value) {
            return $value;
        }

        $decrypted = $this->encryptor->decrypt($value);

        return $decrypted ?: $value;
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
    public function sendOrderTransactions($store = null): bool
    {
        return (bool)$this->scopeConfig->getValue(
            self::XML_PATH_ECOMAIL_PERSONAL_INFORMATION_SEND_ORDER_TRANSACTIONS,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * @param null $store
     * @return bool
     */
    public function updateContactsFromOrders($store = null): bool
    {
        return (bool)$this->scopeConfig->getValue(
            self::XML_PATH_ECOMAIL_PERSONAL_INFORMATION_UPDATE_CONTACTS_FROM_ORDERS,
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
        return $this->sendOrderTransactions($store);
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
    public function sendGroups($store = null): bool
    {
        return (bool)$this->scopeConfig->getValue(
            self::XML_PATH_ECOMAIL_PERSONAL_INFORMATION_SEND_GROUPS,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * @param null $store
     * @return bool
     */
    public function sendLocale($store = null): bool
    {
        return (bool)$this->scopeConfig->getValue(
            self::XML_PATH_ECOMAIL_PERSONAL_INFORMATION_SEND_LOCALE,
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
    public function respectCookieConsent($store = null): bool
    {
        return (bool)$this->scopeConfig->getValue(
            self::XML_PATH_ECOMAIL_TRACKING_RESPECT_COOKIE_CONSENT,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * @param null $store
     * @return bool
     */
    public function isCookieRestrictionEnabled($store = null): bool
    {
        return (bool)$this->scopeConfig->getValue(
            self::XML_PATH_MAGENTO_COOKIE_RESTRICTION,
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
     * @param null $store
     * @return bool
     */
    public function trackProductView($store = null): bool
    {
        return (bool)$this->scopeConfig->getValue(
            self::XML_PATH_ECOMAIL_TRACKING_PRODUCT_VIEW,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * @param null $store
     * @return bool
     */
    public function isFormEnabled($store = null): bool
    {
        return (bool)$this->scopeConfig->getValue(
            self::XML_PATH_ECOMAIL_TRACKING_FORM_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * @param null $store
     * @return string|null
     */
    public function getFormId($store = null)
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_ECOMAIL_TRACKING_FORM_ID,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * @param null $store
     * @return string|null
     */
    public function getFormAccount($store = null)
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_ECOMAIL_TRACKING_FORM_ACCOUNT,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * @param null $store
     * @return string|null
     */
    public function getWebhookToken($store = null)
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_ECOMAIL_GENERAL_WEBHOOK_TOKEN,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * @param null $store
     * @return bool
     */
    public function syncExisting($store = null): bool
    {
        return (bool)$this->scopeConfig->getValue(
            self::XML_PATH_ECOMAIL_GENERAL_SYNC_EXISTING,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * @param null $store
     * @return bool
     */
    public function syncIncludeTags($store = null): bool
    {
        return (bool)$this->scopeConfig->getValue(
            self::XML_PATH_ECOMAIL_GENERAL_SYNC_INCLUDE_TAGS,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * @param null $store
     * @return bool
     */
    public function syncUpdateExisting($store = null): bool
    {
        return (bool)$this->scopeConfig->getValue(
            self::XML_PATH_ECOMAIL_GENERAL_SYNC_UPDATE_EXISTING,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * @param null $store
     * @return int
     */
    public function getSyncCustomerBatchSize($store = null): int
    {
        return max(1, min(3000, (int)$this->scopeConfig->getValue(
            self::XML_PATH_ECOMAIL_GENERAL_SYNC_CUSTOMER_BATCH_SIZE,
            ScopeInterface::SCOPE_STORE,
            $store
        )));
    }

    /**
     * @param null $store
     * @return int
     */
    public function getSyncOrderBatchSize($store = null): int
    {
        return max(1, min(1000, (int)$this->scopeConfig->getValue(
            self::XML_PATH_ECOMAIL_GENERAL_SYNC_ORDER_BATCH_SIZE,
            ScopeInterface::SCOPE_STORE,
            $store
        )));
    }

    /**
     * @param null $store
     * @return string
     */
    public function getCheckoutOptOutLabel($store = null): string
    {
        $label = (string)$this->scopeConfig->getValue(
            self::XML_PATH_ECOMAIL_GENERAL_CHECKOUT_OPT_OUT_LABEL,
            ScopeInterface::SCOPE_STORE,
            $store
        );

        $label = trim($label) ?: 'Do not subscribe me to the newsletter';

        return substr($label, 0, 160);
    }
}
