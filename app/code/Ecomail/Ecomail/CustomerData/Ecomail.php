<?php

namespace Ecomail\Ecomail\CustomerData;

use Ecomail\Ecomail\Helper\Data;
use Magento\Customer\CustomerData\SectionSourceInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\DataObject;

class Ecomail extends DataObject implements SectionSourceInterface
{
    /**
     * @var \Magento\Customer\Model\Session
     */
    private $customerSession;

    /**
     * @var Data
     */
    private $helper;

    /**
     * Ecomail constructor.
     * @param Session $customerSession
     * @param Data $helper
     * @param array $data
     */
    public function __construct(
        Session $customerSession,
        Data  $helper,
        array $data = []
    ) {
        parent::__construct($data);
        $this->customerSession = $customerSession;
        $this->helper = $helper;
    }

    /**
     * @return array
     */
    public function getSectionData(): array
    {
        $sectionData = [];

        if ($this->helper->isTrackingEnabled() && $this->customerSession->getEcomailEmail()) {
            $sectionData['email'] = $this->customerSession->getEcomailEmail();
            $this->customerSession->setEcomailEmail(null);
        }

        return $sectionData;
    }
}
