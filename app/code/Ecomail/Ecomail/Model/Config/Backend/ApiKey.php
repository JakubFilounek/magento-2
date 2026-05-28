<?php

namespace Ecomail\Ecomail\Model\Config\Backend;

use Ecomail\Ecomail\Model\Api;
use Magento\Config\Model\Config\Backend\Encrypted;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Exception\IntegrationException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;

class ApiKey extends Encrypted
{
    /**
     * @var Api
     */
    private $api;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param ScopeConfigInterface $config
     * @param TypeListInterface $cacheTypeList
     * @param EncryptorInterface $encryptor
     * @param Api $api
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ScopeConfigInterface $config,
        TypeListInterface $cacheTypeList,
        EncryptorInterface $encryptor,
        Api $api,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->api = $api;
        parent::__construct($context, $registry, $config, $cacheTypeList, $encryptor, $resource, $resourceCollection, $data);
    }

    /**
     * @return $this
     * @throws LocalizedException
     */
    public function beforeSave()
    {
        $value = (string)$this->getValue();

        if ($this->isValueChanged() && $value !== '' && !preg_match('/^\*+$/', $value)) {
            try {
                $account = $this->api->getAccount($value);
            } catch (IntegrationException $e) {
                throw new LocalizedException(__('Invalid Ecomail API key.'));
            }

            if (($account['message'] ?? '') === 'Wrong api key') {
                throw new LocalizedException(__('Invalid Ecomail API key.'));
            }
        }

        return parent::beforeSave();
    }
}
