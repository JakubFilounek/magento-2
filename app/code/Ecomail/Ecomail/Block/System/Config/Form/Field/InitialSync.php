<?php

namespace Ecomail\Ecomail\Block\System\Config\Form\Field;

use Ecomail\Ecomail\Model\SyncManager;
use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Store\Model\StoreManagerInterface;

class InitialSync extends Field
{
    /**
     * @var string
     */
    protected $_template = 'Ecomail_Ecomail::system/config/form/field/initial_sync.phtml';

    /**
     * @var SyncManager
     */
    private $syncManager;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @param Context $context
     * @param SyncManager $syncManager
     * @param StoreManagerInterface $storeManager
     * @param array $data
     */
    public function __construct(
        Context $context,
        SyncManager $syncManager,
        StoreManagerInterface $storeManager,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->syncManager = $syncManager;
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
    public function getStartUrl(): string
    {
        return $this->getUrl('*/system_config_ecomail/startSync');
    }

    /**
     * @return string
     */
    public function getStatusUrl(): string
    {
        return $this->getUrl('*/system_config_ecomail/syncStatus');
    }

    /**
     * @return array|null
     */
    public function getState(): ?array
    {
        return $this->syncManager->getLatest();
    }

    /**
     * @return int|null
     */
    public function getStoreId(): ?int
    {
        $storeCode = $this->getRequest()->getParam('store');

        if (!$storeCode) {
            return null;
        }

        try {
            return (int)$this->storeManager->getStore($storeCode)->getId();
        } catch (\Exception $e) {
            return null;
        }
    }
}
