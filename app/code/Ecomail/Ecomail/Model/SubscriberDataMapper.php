<?php

namespace Ecomail\Ecomail\Model;

use Ecomail\Ecomail\Helper\Data;
use Exception;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\AddressInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Newsletter\Model\Subscriber;
use Magento\Sales\Api\Data\OrderInterface;

class SubscriberDataMapper
{
    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var AddressRepositoryInterface
     */
    private $addressRepository;

    /**
     * @var Data
     */
    private $helper;

    /**
     * SubscriberDataMapper constructor.
     * @param CustomerRepositoryInterface $customerRepository
     * @param AddressRepositoryInterface $addressRepository
     * @param Data $helper
     */
    public function __construct(
        CustomerRepositoryInterface $customerRepository,
        AddressRepositoryInterface $addressRepository,
        Data $helper
    ) {
        $this->customerRepository = $customerRepository;
        $this->addressRepository = $addressRepository;
        $this->helper = $helper;
    }

    /**
     * @param Subscriber $subscriber
     * @return array
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function map(Subscriber $subscriber): array
    {
        $data['resubscribe'] = true;
        $data['skip_confirmation'] = $this->helper->skipDoubleOptin($subscriber->getStoreId());
        $data['trigger_autoresponders'] = $this->helper->triggerAutoresponders($subscriber->getStoreId());
        $data['subscriber_data']['email'] = $subscriber->getEmail();

        if ($subscriber->getCustomerId()) {
            $customer = $this->customerRepository->getById($subscriber->getCustomerId());

            if ($this->helper->sendName($subscriber->getStoreId())) {
                $data['subscriber_data']['name'] = $customer->getFirstname();
                $data['subscriber_data']['surname'] = $customer->getLastname();
            }

            if ($this->helper->sendDob($subscriber->getStoreId()) && $customer->getDob()) {
                $data['subscriber_data']['birthday'] = $customer->getDob();
            }

            /** @var null|int $addressId */
            $addressId = $this->helper->useShippingAddress($subscriber->getStoreId()) ?
                $customer->getDefaultShipping() : $customer->getDefaultBilling();

            if ($this->helper->sendAddress($subscriber->getStoreId()) && $addressId) {
                /** @var AddressInterface $address */
                $address = $this->getDefaultAddress($addressId);

                if ($address !== null) {
                    $data = $this->generateAddressData($address, $data);
                }
            }
        }

        return $data;
    }

    /**
     * @param OrderInterface $order
     * @return array
     */
    public function mapFromOrder(OrderInterface $order): array
    {
        $data['resubscribe'] = true;
        $data['skip_confirmation'] = $this->helper->skipDoubleOptin($order->getStoreId());
        $data['trigger_autoresponders'] = $this->helper->triggerAutoresponders($order->getStoreId());
        $data['subscriber_data']['email'] = $order->getCustomerEmail();

        if ($this->helper->sendDob($order->getStoreId()) && $order->getCustomerDob()) {
            $data['subscriber_data']['birthday'] = $order->getCustomerDob();
        }

        /** @var AddressInterface $address */
        $address = $this->helper->useShippingAddress($order->getId()) ?
            $order->getShippingAddress() : $order->getBillingAddress();

        if ($this->helper->sendName($order->getStoreId())) {
            $data['subscriber_data']['name'] = $address->getFirstname();
            $data['subscriber_data']['surname'] = $address->getLastname();
        }

        if ($this->helper->sendAddress($order->getStoreId())) {
            $data = $this->generateAddressData($address, $data);
        }

        return $data;
    }

    /**
     * @param $address
     * @param $data
     * @return array
     */
    private function generateAddressData($address, $data): array
    {
        if ($address->getCompany()) {
            $data['subscriber_data']['company'] = $address->getCompany();
        }

        $data['subscriber_data']['street'] = implode(', ', $address->getStreet());
        $data['subscriber_data']['city'] = $address->getCity();
        $data['subscriber_data']['zip'] = $address->getPostcode();
        $data['subscriber_data']['country'] = $address->getCountryId();

        if ($address->getTelephone()) {
            $data['subscriber_data']['phone'] = $address->getTelephone();
        }

        return $data;
    }

    /**
     * @param $addressId
     * @return \Magento\Customer\Api\Data\AddressInterface|null
     */
    private function getDefaultAddress($addressId)
    {
        try {
            return $this->addressRepository->getById($addressId);
        } catch (Exception $e) {
            return null;
        }
    }
}
