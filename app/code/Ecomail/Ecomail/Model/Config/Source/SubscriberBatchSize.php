<?php

namespace Ecomail\Ecomail\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class SubscriberBatchSize implements OptionSourceInterface
{
    /**
     * @return array
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => 100, 'label' => __('100 subscribers per request')],
            ['value' => 250, 'label' => __('250 subscribers per request')],
            ['value' => 500, 'label' => __('500 subscribers per request')],
            ['value' => 1000, 'label' => __('1000 subscribers per request')],
            ['value' => 3000, 'label' => __('3000 subscribers per request')]
        ];
    }
}
