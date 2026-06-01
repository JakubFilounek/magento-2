<?php

namespace Ecomail\Ecomail\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class TransactionBatchSize implements OptionSourceInterface
{
    /**
     * @return array
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => 100, 'label' => __('100 transactions per request')],
            ['value' => 250, 'label' => __('250 transactions per request')],
            ['value' => 500, 'label' => __('500 transactions per request')],
            ['value' => 1000, 'label' => __('1000 transactions per request')]
        ];
    }
}
