<?php

    namespace Ecomail\Ecomail\Model;

    class OptionsListId implements \Magento\Framework\Option\ArrayInterface {

        /**
         * @var \Ecomail\Ecomail\Helper\Data
         */
        protected $helper;

        public function __construct( \Ecomail\Ecomail\Helper\Data $helper ) {
            $this->helper = $helper;
        }

        public function toOptionArray() {

            $options = array();

            if( $this->helper->getScopeConfig()
                             ->getValue(
                                     'ecomail_options/properties/api_key',
                                     \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                             )
            ) {
                $listsCollection = $this->helper->getAPI()
                                                ->getListsCollection();


                foreach( $listsCollection as $list ) {
                    $options[] = array(
                            'value' => $list->id,
                            'label' => $list->name
                    );
                }
            }

            return $options;
        }
    }