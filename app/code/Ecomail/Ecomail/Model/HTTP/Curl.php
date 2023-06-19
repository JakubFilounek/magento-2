<?php

namespace Ecomail\Ecomail\Model\HTTP;

use Magento\Framework\HTTP\Client\Curl as MagentoCurl;

class Curl extends MagentoCurl
{
    /**
     * Make DELETE request
     *
     * @param string $uri
     * @param array|string|null $params
     * @return void
     */
    public function delete($uri, $params = null)
    {
        if ($params === null) {
            $this->makeRequest('DELETE', $uri);
            return;
        }
        $options = $this->_curlUserOptions;
        $this->_curlUserOptions[CURLOPT_POSTFIELDS] = is_array($params) ? http_build_query($params) : $params;
        $this->makeRequest('DELETE', $uri);
        $this->_curlUserOptions = $options;
    }
}
