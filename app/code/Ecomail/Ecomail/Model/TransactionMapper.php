<?php

namespace Ecomail\Ecomail\Model;

use Ecomail\Ecomail\Helper\Data;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Customer\Api\Data\AddressInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderItemInterface;

class TransactionMapper
{
    /**
     * @var Data
     */
    private $helper;

    /**
     * @var CollectionFactory
     */
    private $categoryCollectionFactory;

    /**
     * TransactionMapper constructor.
     * @param Data $helper
     * @param CollectionFactory $categoryCollectionFactory
     */
    public function __construct(
        Data $helper,
        CollectionFactory $categoryCollectionFactory
    ) {
        $this->helper = $helper;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
    }

    /**
     * @param OrderInterface $order
     * @return array
     * @throws LocalizedException
     */
    public function map(OrderInterface $order): array
    {
        $data = [];

        $data['transaction']['order_id'] = $order->getIncrementId();
        $data['transaction']['email'] = $order->getCustomerEmail();
        $data['transaction']['shop'] = $order->getStore()->getName();
        $data['transaction']['amount'] = $order->getGrandTotal();
        $data['transaction']['tax'] = $order->getTaxAmount();
        $data['transaction']['shipping'] = $order->getShippingInclTax();
        $timestamp = $this->getOrderTimestamp($order);
        $data['transaction']['timestamp'] = $timestamp;

        if ($this->helper->sendAddress($order->getStoreId())) {
            /** @var AddressInterface $address */
            $address = $this->helper->useShippingAddress($order->getStoreId()) ?
                $order->getShippingAddress() : $order->getBillingAddress();

            if ($address === null) {
                $address = $order->getBillingAddress();
            }

            if ($address !== null) {
                $data['transaction']['street'] = implode(', ', $address->getStreet());
                $data['transaction']['city'] = $address->getCity();
                $data['transaction']['zip'] = $address->getPostcode();
                $data['transaction']['country'] = $address->getCountryId();

                if ($address->getTelephone()) {
                    $data['transaction']['phone'] = $address->getTelephone();
                }
            }
        }

        $items = [];

        /** @var OrderItemInterface $orderItem */
        foreach ($order->getAllVisibleItems() as $orderItem) {
            // Skip child items so configurable and bundle products are not duplicated.
            if ($orderItem->getParentItem()) {
                continue;
            }

            /** @var ProductInterface $product */
            $product = $orderItem->getProduct();
            $categories = $this->getCategoryNames($product->getCategoryIds());
            $categoryString = implode(' | ', $categories);

            $items[] = [
                'code' => $orderItem->getSku(),
                'title' => $orderItem->getName(),
                'category' => $categoryString,
                'price' => $orderItem->getPriceInclTax(),
                'amount' => $orderItem->getQtyOrdered(),
                'timestamp' => $timestamp,
                'categories' => $categories
            ];
        }

        $data['transaction_items'] = $items;

        return $data;
    }

    /**
     * @param array $categoryIds
     * @return string
     * @throws LocalizedException
     */
    private function getCategoryNames(array $categoryIds): array
    {
        if (empty($categoryIds)) {
            return [];
        }

        $categoryCollection = $this->categoryCollectionFactory
            ->create()
            ->addFieldToFilter('entity_id', ['in' => $categoryIds])
            ->addAttributeToSelect('name');

        return $categoryCollection->getColumnValues('name');
    }

    /**
     * @param OrderInterface $order
     * @return int
     */
    private function getOrderTimestamp(OrderInterface $order): int
    {
        $timestamp = strtotime((string)$order->getCreatedAt());

        return $timestamp ? (int)$timestamp : time();
    }
}
