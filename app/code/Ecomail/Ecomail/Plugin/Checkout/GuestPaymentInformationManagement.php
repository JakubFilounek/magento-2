<?php

namespace Ecomail\Ecomail\Plugin\Checkout;

use Magento\Checkout\Api\GuestPaymentInformationManagementInterface;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Api\Data\PaymentInterface;

class GuestPaymentInformationManagement
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
     * @param GuestPaymentInformationManagementInterface $subject
     * @param string $cartId
     * @param string $email
     * @param PaymentInterface $paymentMethod
     * @param AddressInterface|null $billingAddress
     * @return array
     */
    public function beforeSavePaymentInformationAndPlaceOrder(
        GuestPaymentInformationManagementInterface $subject,
        $cartId,
        $email,
        PaymentInterface $paymentMethod,
        ?AddressInterface $billingAddress = null
    ): array {
        $extensionAttributes = $paymentMethod->getExtensionAttributes();
        $optOut = $extensionAttributes && method_exists($extensionAttributes, 'getEcomailNewsletterOptOut')
            ? (bool)$extensionAttributes->getEcomailNewsletterOptOut()
            : false;

        $this->checkoutSession->setData('ecomail_newsletter_opt_out', $optOut);

        return [$cartId, $email, $paymentMethod, $billingAddress];
    }
}
