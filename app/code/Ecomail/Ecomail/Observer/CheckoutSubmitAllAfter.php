<?php

    namespace Ecomail\Ecomail\Observer;

    use Magento\Framework\Event\ObserverInterface;
    use Magento\Framework\UrlInterface;

    class CheckoutSubmitAllAfter implements ObserverInterface {

        protected $helper;
        protected $categoryRepository;

        /**
         * @param \Ecomail\Ecomail\Helper\Data              $helper
         * @param \Magento\Catalog\Model\CategoryRepository $categoryRepository
         */
        public function __construct( \Ecomail\Ecomail\Helper\Data $helper, \Magento\Catalog\Model\CategoryRepository $categoryRepository ) {
            $this->helper             = $helper;
            $this->categoryRepository = $categoryRepository;
        }

        public function execute( \Magento\Framework\Event\Observer $observer ) {
            $event   = $observer->getEvent();
            $order   = $observer->getOrder();

            $addressDelivery = $order->getShippingAddress();

            /**
             * @var \Magento\Sales\Model\Order      $order
             * @var \Magento\Sales\Model\Order\Item $orderProduct
             * @var \Magento\Catalog\Model\Product  $product
             */

            $arr = array();
            foreach( $order->getAllItems() as $orderProduct ) {
                $product     = $orderProduct->getProduct();
                $categoryIds = $product->getCategoryIds();

                if( count( $categoryIds ) ) {
                    $firstCategoryId = $categoryIds[0];
                    $category        = $this->categoryRepository->get( $firstCategoryId );

                }

                if( empty( $orderProduct['price_incl_tax'] ) ) {
                    continue;
                }

                $arr[] = array(
                        'code'      => $orderProduct['sku'],
                        'title'     => $orderProduct['name'],
                        'category'  => $category->getName(),
                        'price'     => $orderProduct['price_incl_tax'],
                        'amount'    => $orderProduct['qty_ordered'],
                        'timestamp' => strtotime( $orderProduct['created_at'] )
                );
            }

            $data = array(
                    'transaction'       => array(
                            'order_id'  => $order->getId(),
                            'email'     => $order['customer_email'],
                            'shop'      => $order->getStore()
                                                 ->getBaseUrl( UrlInterface::URL_TYPE_LINK ),
                            'amount'    => $order['grand_total'],
                            'tax'       => $order['tax_amount'],
                            'shipping'  => $order['shipping_incl_tax'],
                            'city'      => $addressDelivery['city'],
                            'county'    => '',
                            'country'   => $addressDelivery->getCountryId(),
                            'timestamp' => strtotime( $order['created_at'] )
                    ),
                    'transaction_items' => $arr
            );

            $r = Mage::helper( 'ecomail' )
                     ->getApi()
                     ->createTransaction( $data );

            return $observer;
        }
    }