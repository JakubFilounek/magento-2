<?php

namespace Ecomail\Ecomail\Block;

use Ecomail\Ecomail\Helper\Data;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

class Tracking extends Template
{
    /**
     * @var Data
     */
    private $helper;

    /**
     * @var Registry
     */
    private $registry;

    /**
     * @param Context $context
     * @param Data $helper
     * @param Registry $registry
     * @param array $data
     */
    public function __construct(
        Context $context,
        Data $helper,
        Registry $registry,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->helper = $helper;
        $this->registry = $registry;
    }

    /**
     * @return bool
     */
    public function isTrackingEnabled(): bool
    {
        return $this->helper->isTrackingEnabled((int)$this->_storeManager->getStore()->getId());
    }

    /**
     * @return string
     */
    public function getAppId(): string
    {
        return (string)$this->helper->getAppId((int)$this->_storeManager->getStore()->getId());
    }

    /**
     * @return bool
     */
    public function shouldWaitForCookieConsent(): bool
    {
        $storeId = (int)$this->_storeManager->getStore()->getId();

        return $this->helper->respectCookieConsent($storeId)
            && $this->helper->isCookieRestrictionEnabled($storeId);
    }

    /**
     * @return bool
     */
    public function shouldTrackProductView(): bool
    {
        return $this->isTrackingEnabled()
            && $this->helper->trackProductView((int)$this->_storeManager->getStore()->getId())
            && $this->getCurrentProductCode() !== '';
    }

    /**
     * @return string
     */
    public function getCurrentProductCode(): string
    {
        $product = $this->registry->registry('current_product');

        if (!$product instanceof ProductInterface) {
            return '';
        }

        return (string)$product->getSku();
    }

    /**
     * @return bool
     */
    public function isFormEnabled(): bool
    {
        $storeId = (int)$this->_storeManager->getStore()->getId();

        return $this->helper->isFormEnabled($storeId)
            && $this->getFormId() !== ''
            && $this->getFormAccount() !== '';
    }

    /**
     * @return string
     */
    public function getFormId(): string
    {
        return (string)$this->helper->getFormId((int)$this->_storeManager->getStore()->getId());
    }

    /**
     * @return string
     */
    public function getFormAccount(): string
    {
        return (string)$this->helper->getFormAccount((int)$this->_storeManager->getStore()->getId());
    }
}
