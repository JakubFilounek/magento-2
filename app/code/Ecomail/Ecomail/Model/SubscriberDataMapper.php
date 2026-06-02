<?php

namespace Ecomail\Ecomail\Model;

use Ecomail\Ecomail\Helper\Data;
use Exception;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\AddressInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Api\GroupRepositoryInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Newsletter\Model\Subscriber;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Store\Model\ScopeInterface;

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
     * @var GroupRepositoryInterface
     */
    private $groupRepository;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var Data
     */
    private $helper;

    /**
     * SubscriberDataMapper constructor.
     * @param CustomerRepositoryInterface $customerRepository
     * @param AddressRepositoryInterface $addressRepository
     * @param GroupRepositoryInterface $groupRepository
     * @param ScopeConfigInterface $scopeConfig
     * @param Data $helper
     */
    public function __construct(
        CustomerRepositoryInterface $customerRepository,
        AddressRepositoryInterface $addressRepository,
        GroupRepositoryInterface $groupRepository,
        ScopeConfigInterface $scopeConfig,
        Data $helper
    ) {
        $this->customerRepository = $customerRepository;
        $this->addressRepository = $addressRepository;
        $this->groupRepository = $groupRepository;
        $this->scopeConfig = $scopeConfig;
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
        $customerGroupId = null;

        if ($subscriber->getCustomerId()) {
            $customer = $this->customerRepository->getById($subscriber->getCustomerId());
            $customerGroupId = (int)$customer->getGroupId();

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

        $data['subscriber_data'] = array_merge(
            $data['subscriber_data'],
            $this->generateContextData($subscriber->getStoreId(), $customerGroupId, true)
        );

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
        $address = $this->helper->useShippingAddress($order->getStoreId()) ?
            $order->getShippingAddress() : $order->getBillingAddress();

        if ($address === null) {
            $address = $order->getBillingAddress();
        }

        if ($address !== null && $this->helper->sendName($order->getStoreId())) {
            $data['subscriber_data']['name'] = $address->getFirstname();
            $data['subscriber_data']['surname'] = $address->getLastname();
        }

        if ($address !== null && $this->helper->sendAddress($order->getStoreId())) {
            $data = $this->generateAddressData($address, $data);
        }

        $data['subscriber_data'] = array_merge(
            $data['subscriber_data'],
            $this->generateContextData($order->getStoreId(), $order->getCustomerGroupId(), true)
        );

        return $data;
    }

    /**
     * @param CustomerInterface $customer
     * @param bool $newsletterSubscriber
     * @return array
     */
    public function mapFromCustomer(CustomerInterface $customer, bool $newsletterSubscriber = false): array
    {
        $storeId = $customer->getStoreId();

        $data['resubscribe'] = true;
        $data['skip_confirmation'] = $this->helper->skipDoubleOptin($storeId);
        $data['trigger_autoresponders'] = $this->helper->triggerAutoresponders($storeId);
        $data['subscriber_data']['email'] = $customer->getEmail();

        if ($this->helper->sendName($storeId)) {
            $data['subscriber_data']['name'] = $customer->getFirstname();
            $data['subscriber_data']['surname'] = $customer->getLastname();
        }

        if ($this->helper->sendDob($storeId) && $customer->getDob()) {
            $data['subscriber_data']['birthday'] = $customer->getDob();
        }

        $address = $this->getCustomerDefaultAddress($customer);

        if ($this->helper->sendAddress($storeId) && $address !== null) {
            $data = $this->generateAddressData($address, $data);
        }

        $data['subscriber_data'] = array_merge(
            $data['subscriber_data'],
            $this->generateContextData($storeId, $customer->getGroupId(), $newsletterSubscriber)
        );

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

    /**
     * @param CustomerInterface $customer
     * @return AddressInterface|null
     */
    private function getCustomerDefaultAddress(CustomerInterface $customer)
    {
        $addressId = $this->helper->useShippingAddress($customer->getStoreId()) ?
            $customer->getDefaultShipping() : $customer->getDefaultBilling();

        if ($addressId) {
            return $this->getDefaultAddress($addressId);
        }

        $addresses = $customer->getAddresses();

        return $addresses ? reset($addresses) : null;
    }

    /**
     * @param int|null $storeId
     * @param int|null $customerGroupId
     * @param bool $newsletterSubscriber
     * @return array
     */
    private function generateContextData($storeId, $customerGroupId = null, bool $newsletterSubscriber = false): array
    {
        $data = [];
        $tags = ['magento'];

        if ($newsletterSubscriber) {
            $tags[] = 'magento_newsletter';
        }

        if ($this->helper->sendGroups($storeId)) {
            if ($customerGroupId !== null) {
                try {
                    $group = $this->groupRepository->getById((int)$customerGroupId);
                    $tags[] = $group->getCode();
                } catch (Exception $e) {
                    // Missing groups should not block subscription sync.
                }
            }
        }

        if (!empty($tags)) {
            $data['tags'] = array_values(array_unique($tags));
        }

        if ($this->helper->sendLocale($storeId)) {
            $locale = $this->scopeConfig->getValue(
                'general/locale/code',
                ScopeInterface::SCOPE_STORE,
                $storeId
            );

            if ($locale) {
                $data['custom_fields']['MAGENTO_LANGUAGE'] = $locale;
            }
        }

        return $data;
    }
}
