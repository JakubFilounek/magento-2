<?php
    namespace Ecomail\Ecomail\Controller\Adminhtml\Ecomail;

    class Ajax extends \Magento\Backend\App\Action {

        protected $helper;

        /**
         * @param \Magento\Backend\App\Action\Context              $context
         * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
         * @param \Ecomail\Ecomail\Helper\Data                     $helper
         */
        public function __construct( \Magento\Backend\App\Action\Context $context, \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory, \Ecomail\Ecomail\Helper\Data $helper ) {
            parent::__construct( $context );
            $this->resultJsonFactory = $resultJsonFactory;
            $this->helper            = $helper;
        }

        /**
         * @return \Magento\Framework\Controller\Result\Json
         */
        public function execute() {
            /** @var \Magento\Framework\Controller\Result\Json $result */
            $resultJson = $this->resultJsonFactory->create();

            $isAjax = $this->getRequest()
                           ->isAjax();
            if( $isAjax ) {

                $result = array();

                $cmd = $this->getRequest()
                            ->getParam( 'cmd' );
                if( $cmd == 'getLists' ) {

                    $APIKey = $this->getRequest()
                                   ->getParam( 'APIKey' );
                    if( $APIKey ) {
                        $listsCollection = $this->helper->getAPI()
                                                        ->setAPIKey( $APIKey )
                                                        ->getListsCollection();
                        if( $listsCollection ) {
                            foreach( $listsCollection as $list ) {
                                $result[] = array(
                                        'id'   => $list->id,
                                        'name' => $list->name
                                );
                            }
                        }
                    }

                }

                return $resultJson->setData( $result );
            }
        }
    }