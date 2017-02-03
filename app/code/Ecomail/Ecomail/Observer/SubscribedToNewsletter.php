<?php

    namespace Ecomail\Ecomail\Observer;

    use Magento\Framework\Event\ObserverInterface;

    class SubscribedToNewsletter implements ObserverInterface {

        protected $helper;
        protected $customerRegistry;

        /**
         * @param \Ecomail\Ecomail\Helper\Data             $helper
         * @param \Magento\Customer\Model\CustomerRegistry $customerRegistry
         */
        public function __construct( \Ecomail\Ecomail\Helper\Data $helper, \Magento\Customer\Model\CustomerRegistry $customerRegistry ) {
            $this->helper           = $helper;
            $this->customerRegistry = $customerRegistry;
        }

        public function execute( \Magento\Framework\Event\Observer $observer ) {
            $event      = $observer->getEvent();
            $subscriber = $event->getDataObject();
            $data       = $subscriber->getData();
            
            $statusChange = $subscriber->isStatusChanged();
   
            // Trigger if user is now subscribed and there has been a status change:
            if( $data['subscriber_status'] == "1" && $statusChange == true ) {

                if( $this->helper->getScopeConfig()
                                 ->getValue( 'ecomail_options/properties/api_key' )
                ) {

                    $email = $data['subscriber_email'];
                    $name  = '';

                    $id = $subscriber['customer_id'];
                    if( $id ) {
                        $customer = $this->customerRegistry->retrieve( $id );
                        $name     = $customer->getName();
                    }

                    $this->helper->getApi()
                                 ->subscribeToList(
                                         $this->helper->getScopeConfig()
                                                      ->getValue( 'ecomail_options/properties/list_id' ),
                                         array(
                                                 'email' => $email,
                                                 'name'  => $name
                                         )
                                 );
                }

            }

            return $observer;
        }
    }