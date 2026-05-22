<?php

namespace Ecomail\Ecomail\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class Address implements OptionSourceInterface
{
    const SHIPPING_ADDRESS = 1;
    const BILLING_ADDRESS = 2;

    /**
     * @return array[]
     */
    public function toOptionArray(): array
    {
        return [
            [
                'label' => __('Shipping Address'),
                'value' => self::SHIPPING_ADDRESS
            ],
            [
                'label' => __('Billing Address'),
                'value' => self::BILLING_ADDRESS
            ]
        ];
    }
}
