<?php

namespace Ecomail\Ecomail\Block\System\Config\Form\Field;

use Ecomail\Ecomail\Helper\Data;
use Ecomail\Ecomail\Model\Api;
use Exception;
use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class Status extends Field
{
    /**
     * @var string
     */
    protected $_template = 'Ecomail_Ecomail::system/config/form/field/status.phtml';

    /**
     * @var Data
     */
    private $helper;

    /**
     * @var Api
     */
    private $api;

    /**
     * Status constructor.
     * @param Context $context
     * @param Data $helper
     * @param Api $api
     * @param array $data
     */
    public function __construct(
        Context $context,
        Data $helper,
        Api $api,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->helper = $helper;
        $this->api = $api;
    }

    /**
     * @return string
     */
    public function getStatusCode(): string
    {
        if (!$this->helper->isEnabled()) {
            return 'Inactive';
        }

        try {
            $this->api->getSubscriberLists();
            return 'Active';
        } catch (Exception $e) {
            return 'Error';
        }
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
