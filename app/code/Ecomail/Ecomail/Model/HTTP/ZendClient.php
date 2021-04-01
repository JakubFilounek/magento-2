<?php

namespace Ecomail\Ecomail\Model\HTTP;

use Magento\Framework\HTTP\ZendClient as MagentoZendClient;
use Zend_Http_Client_Exception;

/**
 * Because 2.2 and 2.3 do not support DELETE request.
 */
class ZendClient extends MagentoZendClient
{

    /**
     * @return $this|ZendClient
     * @throws Zend_Http_Client_Exception
     */
    protected function _trySetCurlAdapter()
    {
        if (extension_loaded('curl')) {
            $this->setAdapter(new Adapter\Curl());
        }
        return $this;
    }
}
