<?php
namespace Ecomail\Ecomail\Plugin\Framework\App\Request;

use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\RequestInterface;

class CsrfValidator
{
    /**
     * Bypass CSRF validation
     *
     * @param \Magento\Framework\App\Request\CsrfValidator $validator
     * @param callable $validate
     * @param RequestInterface $request
     * @param ActionInterface $action
     * @return void
     * @SuppressWarnings("PMD.UnusedFormalParameter")
     */
    public function aroundValidate(
        \Magento\Framework\App\Request\CsrfValidator $validator,
        callable $validate,
        RequestInterface $request,
        ActionInterface $action
    ) {
        if ($action instanceof \Ecomail\Ecomail\Controller\Webhook\Action) {
            return;
        }
        $validate($request, $action);
    }
}
