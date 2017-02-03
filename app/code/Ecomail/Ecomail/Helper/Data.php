<?php

    namespace Ecomail\Ecomail\Helper;

    use EcomailAPI;

    class Data extends \Magento\Framework\App\Helper\AbstractHelper {

        public function getScopeConfig() {
            return $this->scopeConfig;
        }

        public function getRequest() {
            return $this->_getRequest();
        }

        public function getAPI() {

            require_once __DIR__ . '/../lib/api.php';

            $obj = new EcomailAPI();
            $obj->setAPIKey(
                    $this->scopeConfig->getValue(
                            'ecomail_options/properties/api_key',
                            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                    )
            );

            return $obj;
        }

        public function getCookieNameTrackStructEvent() {
            return 'Ecomail';
        }

    }