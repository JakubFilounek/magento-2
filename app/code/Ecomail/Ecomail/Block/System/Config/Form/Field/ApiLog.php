<?php

namespace Ecomail\Ecomail\Block\System\Config\Form\Field;

use Ecomail\Ecomail\Model\ApiLog as ApiLogModel;
use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class ApiLog extends Field
{
    /**
     * @var string
     */
    protected $_template = 'Ecomail_Ecomail::system/config/form/field/api_log.phtml';

    /**
     * @var ApiLogModel
     */
    private $apiLog;

    /**
     * @param Context $context
     * @param ApiLogModel $apiLog
     * @param array $data
     */
    public function __construct(
        Context $context,
        ApiLogModel $apiLog,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->apiLog = $apiLog;
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
     * @return array
     */
    public function getRecentLogs(): array
    {
        return $this->apiLog->getRecent(10);
    }
}
