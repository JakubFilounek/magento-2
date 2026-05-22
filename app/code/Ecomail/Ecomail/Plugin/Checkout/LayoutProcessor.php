<?php

namespace Ecomail\Ecomail\Plugin\Checkout;

use Ecomail\Ecomail\Helper\Data;

class LayoutProcessor
{
    /**
     * @var Data
     */
    private $helper;

    /**
     * @param Data $helper
     */
    public function __construct(Data $helper)
    {
        $this->helper = $helper;
    }

    /**
     * @param \Magento\Checkout\Block\Checkout\LayoutProcessor $subject
     * @param array $jsLayout
     * @return array
     */
    public function afterProcess(\Magento\Checkout\Block\Checkout\LayoutProcessor $subject, array $jsLayout): array
    {
        if (!isset($jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']
            ['payment']['children']['payments-list']['children']['before-place-order']['children'])) {
            return $jsLayout;
        }

        $path =& $jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']
        ['payment']['children']['payments-list']['children']['before-place-order']['children'];

        $path['ecomail-newsletter-opt-out'] = [
            'component' => 'Ecomail_Ecomail/js/view/newsletter-opt-out',
            'sortOrder' => 20,
            'config' => [
                'template' => 'Ecomail_Ecomail/newsletter-opt-out',
                'label' => $this->helper->getCheckoutOptOutLabel(),
            ],
        ];

        return $jsLayout;
    }
}
