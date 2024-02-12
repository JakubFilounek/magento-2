<?php

namespace Ecomail\Ecomail\Model;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Helper\Image;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Magento\Quote\Model\Quote;

class BasketEventMapper
{

    /**
     * @var Image
     */
    private $imageHelper;

    /**
     * @var JsonSerializer
     */
    private $jsonSerializer;

    /**
     * BasketEventMapper constructor.
     * @param Image $imageHelper
     * @param JsonSerializer $jsonSerializer
     */
    public function __construct(Image $imageHelper, JsonSerializer $jsonSerializer)
    {
        $this->imageHelper = $imageHelper;
        $this->jsonSerializer = $jsonSerializer;
    }

    /**
     * @param Quote $quote
     * @return array
     */
    public function map(Quote $quote): array
    {
        $data = [];
        $data['event'] = [
            'email' => $quote->getCustomerEmail(),
            'category' => 'ue',
            'action' => 'Basket',
            'label' => 'Basket',
        ];

        $items = [];

        foreach ($quote->getAllVisibleItems() as $quoteItem) {
            if ($quoteItem->getParentItem()) {
                continue;
            }

            /** @var ProductInterface $product */
            $product = $quoteItem->getProduct();

            $items[] = [
                'productId' => $quoteItem->getSku(),
                'img_url' => $this->imageHelper->init($product, 'product_thumbnail_image')->getUrl(),
                'url' => $product->getProductUrl(),
                'name' => $quoteItem->getName(),
                'price' => $quoteItem->getPriceInclTax(),
                'description' => $product->getShortDescription()
            ];
        }

        $eventValue = [];
        $eventValue['data']['data'] = [
            'action' => 'Basket',
            'products' => $items
        ];

        $data['event']['value'] = $this->jsonSerializer->serialize($eventValue);

        return $data;
    }
}
