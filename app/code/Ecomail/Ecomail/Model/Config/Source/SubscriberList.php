<?php

namespace Ecomail\Ecomail\Model\Config\Source;

use Ecomail\Ecomail\Model\Api;
use Exception;
use Magento\Framework\Data\OptionSourceInterface;

class SubscriberList implements OptionSourceInterface
{
    /**
     * @var Api
     */
    private $api;

    public function __construct(Api $api)
    {
        $this->api = $api;
    }

    public function toOptionArray(): array
    {
        $options = [];

        try {
            $subscriberLists = $this->api->getSubscriberLists();

            foreach ($subscriberLists as $list) {
                $options[] = [
                    'label' => $list['name'],
                    'value' => $list['id'],
                ];
            }
        } catch (Exception $e) {
            return [];
        }

        return $options;
    }
}
