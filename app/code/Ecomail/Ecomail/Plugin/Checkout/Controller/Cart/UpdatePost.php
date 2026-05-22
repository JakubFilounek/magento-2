<?php

namespace Ecomail\Ecomail\Plugin\Checkout\Controller\Cart;

use Ecomail\Ecomail\Model\EventManager;
use Magento\Checkout\Controller\Cart\UpdatePost as OriginalUpdatePost;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

class UpdatePost
{
    /**
     * @var EventManager
     */
    private $eventManager;

    /**
     * Add constructor.
     * @param EventManager $eventManager
     */
    public function __construct(
        EventManager $eventManager
    ) {
        $this->eventManager = $eventManager;
    }

    /**
     * @param OriginalUpdatePost $subject
     * @param $result
     * @return mixed
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function afterExecute(
        OriginalUpdatePost $subject,
        $result
    ) {
        $this->eventManager->updateCartContent();
        return $result;
    }
}
