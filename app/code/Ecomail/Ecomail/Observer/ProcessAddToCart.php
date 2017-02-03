<?php

    namespace Ecomail\Ecomail\Observer;

    use Magento\Framework\Event\ObserverInterface;

    class ProcessAddToCart implements ObserverInterface {

        protected $helper;

        /**
         * @param \Ecomail\Ecomail\Helper\Data $helper
         */
        public function __construct( \Ecomail\Ecomail\Helper\Data $helper ) {
            $this->helper = $helper;
        }

        public function execute( \Magento\Framework\Event\Observer $observer ) {
            $event = $observer->getEvent();
            /**
             * @var \Magento\Catalog\Model\Product $product
             */
            $product = $observer->getProduct();

            $params     = $this->helper->getRequest()
                                       ->getParams();
            $quantity   = $params['qty'];
            $id_product = $product->getId();

            setcookie(
                    $this->helper->getCookieNameTrackStructEvent(),
                    json_encode(
                            array(
                                    'category' => 'Product',
                                    'action'   => 'AddToCart',
                                    'tag'      => implode(
                                            '|',
                                            array(
                                                    $id_product
                                            )
                                    ),
                                    'property' => 'quantity',
                                    'value'    => $quantity
                            )
                    ),
                    null,
                    $this->helper->getRequest()
                                 ->getBasePath()
            );

            return $observer;
        }
    }