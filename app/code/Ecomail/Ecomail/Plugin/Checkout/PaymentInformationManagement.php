<?php

namespace Ecomail\Ecomail\Plugin\Checkout;

use Magento\Checkout\Api\PaymentInformationManagementInterface;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Api\Data\PaymentInterface;

class PaymentInformationManagement
{
    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * @param CheckoutSession $checkoutSession
     */
    public function __construct(CheckoutSession $checkoutSession)
    {
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * @param PaymentInformationManagementInterface $subject
     * @param int $cartId
     * @param PaymentInterface $paymentMethod
     * @param AddressInterface|null $billingAddress
     * @return array
     */
    public function beforeSavePaymentInformationAndPlaceOrder(
        PaymentInformationManagementInterface $subject,
        $cartId,
        PaymentInterface $paymentMethod,
        ?AddressInterface $billingAddress = null
    ): array {
        $this->storeOptOut($paymentMethod);

        return [$cartId, $paymentMethod, $billingAddress];
    }

    /**
     * @param PaymentInterface $paymentMethod
     */
    private function storeOptOut(PaymentInterface $paymentMethod): void
    {
        $extensionAttributes = $paymentMethod->getExtensionAttributes();
        $optOut = $extensionAttributes && method_exists($extensionAttributes, 'getEcomailNewsletterOptOut')
            ? (bool)$extensionAttributes->getEcomailNewsletterOptOut()
            : false;

        $this->checkoutSession->setData('ecomail_newsletter_opt_out', $optOut);
    }
}
