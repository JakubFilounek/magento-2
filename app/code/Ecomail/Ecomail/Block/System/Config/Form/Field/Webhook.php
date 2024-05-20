<?php

namespace Ecomail\Ecomail\Block\System\Config\Form\Field;

use Ecomail\Ecomail\Helper\Data;
use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
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
     * @return string
     */
    public function getWebhookUrl(): string
    {
        $baseUrl = $this->storeManager->getDefaultStoreView()->getBaseUrl();
        return rtrim($baseUrl, '/') . '/ecomail/webhook/action/' . $this->helper->getWebhookHash() . '/';
    }

    /**
     * Return element html
     *
     * @param AbstractElement $element
     * @return string
     *
     */
    protected function _getElementHtml(AbstractElement $element): string
    {
        return $this->_toHtml();
    }
}
