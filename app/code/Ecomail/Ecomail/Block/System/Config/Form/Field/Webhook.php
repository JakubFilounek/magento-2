<?php

namespace Ecomail\Ecomail\Block\System\Config\Form\Field;

use Ecomail\Ecomail\Helper\Data;
use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\UrlInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;

class Webhook extends Field
{
    /**
     * @var string
     */
    protected $_template = 'Ecomail_Ecomail::system/config/form/field/webhook.phtml';

    /**
     * @var Data
     */
    private $helper;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @param Context $context
     * @param Data $helper
     * @param StoreManagerInterface $storeManager
     * @param array $data
     */
    public function __construct(
        Context $context,
        Data $helper,
        StoreManagerInterface $storeManager,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->helper = $helper;
        $this->storeManager = $storeManager;
    }

    /**
     * @param AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element): string
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();

        return parent::render($element);
    }

    /**
     * @param AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element): string
    {
        return $this->_toHtml();
    }

    /**
     * @return string
     */
    public function getWebhookUrlTemplate(): string
    {
        $store = $this->getCurrentStore();
        $baseUrl = rtrim($store->getBaseUrl(UrlInterface::URL_TYPE_LINK), '/');

        return sprintf(
            '%s/ecomail/webhook/subscriber?token=__TOKEN__&store=%d',
            $baseUrl,
            (int)$store->getId()
        );
    }

    /**
     * @return string
     */
    public function getWebhookToken(): string
    {
        return (string)$this->helper->getWebhookToken((int)$this->getCurrentStore()->getId());
    }

    /**
     * @return StoreInterface
     */
    private function getCurrentStore(): StoreInterface
    {
        $storeCode = $this->getRequest()->getParam('store');
        $websiteCode = $this->getRequest()->getParam('website');

        try {
            if ($storeCode) {
                return $this->storeManager->getStore($storeCode);
            }

            if ($websiteCode) {
                $website = $this->storeManager->getWebsite($websiteCode);
                $defaultStore = $website->getDefaultStore();

                if ($defaultStore) {
                    return $defaultStore;
                }
            }

            return $this->storeManager->getStore();
        } catch (NoSuchEntityException $e) {
            return $this->storeManager->getDefaultStoreView();
        }
    }
}
