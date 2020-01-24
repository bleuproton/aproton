<?php

namespace Marello\Bundle\InventoryBundle\EventListener;

use Oro\Component\MessageQueue\Client\MessageProducerInterface;

use Marello\Bundle\InventoryBundle\Async\Topics;
use Marello\Bundle\SalesBundle\Entity\SalesChannelGroup;
use Marello\Bundle\ProductBundle\Entity\ProductInterface;
use Marello\Bundle\InventoryBundle\Event\BalancedInventoryUpdateEvent;
use Marello\Bundle\InventoryBundle\Model\InventoryUpdateContext;
use Marello\Bundle\InventoryBundle\Entity\BalancedInventoryLevel;
use Marello\Bundle\InventoryBundle\Model\BalancedInventoryLevelInterface;
use Marello\Bundle\InventoryBundle\Entity\Repository\BalancedInventoryRepository;
use Marello\Bundle\InventoryBundle\Model\InventoryBalancer\InventoryBalancerTriggerCalculator;

class BalancedInventoryUpdateAfterEventListener
{
    const BALANCED_LEVEL_CONTEXT_KEY = 'balancedInventoryLevel';
    const SALESCHANNELGROUP_CONTEXT_KEY  = 'salesChannelGroup';

    /** @var MessageProducerInterface $messageProducer */
    private $messageProducer;

    /** @var InventoryBalancerTriggerCalculator $triggerCalculator */
    private $triggerCalculator;

    /** @var BalancedInventoryRepository $repository */
    private $repository;

    /**
     * BalancedInventoryUpdateAfterEventListener constructor.
     * @param MessageProducerInterface $messageProducer
     * @param InventoryBalancerTriggerCalculator $triggerCalculator
     * @param BalancedInventoryRepository $repository
     */
    public function __construct(
        MessageProducerInterface $messageProducer,
        InventoryBalancerTriggerCalculator $triggerCalculator,
        BalancedInventoryRepository $repository
    ) {
        $this->messageProducer = $messageProducer;
        $this->triggerCalculator = $triggerCalculator;
        $this->repository = $repository;
    }

    /**
     * Handle incoming event
     * @param BalancedInventoryUpdateEvent $event
     * @return mixed
     */
    public function handleInventoryUpdateAfterEvent(BalancedInventoryUpdateEvent $event)
    {
        /** @var InventoryUpdateContext $context */
        $context = $event->getInventoryUpdateContext();
        if (!$context->getIsVirtual()) {
            // do nothing when context isn't for virtual inventory levels
            return;
        }

        if (!$context->getValue(self::BALANCED_LEVEL_CONTEXT_KEY)
            || !$context->getValue(self::SALESCHANNELGROUP_CONTEXT_KEY)
        ) {
            throw new \InvalidArgumentException(
                sprintf(
                    'To few arguments given in the context, no %s or %s given, please check your data',
                    self::BALANCED_LEVEL_CONTEXT_KEY,
                    self::SALESCHANNELGROUP_CONTEXT_KEY
                )
            );
        }

        /** @var ProductInterface $product */
        $product = $context->getProduct();
        $level = $context->getValue(self::BALANCED_LEVEL_CONTEXT_KEY);
        $group = $context->getValue(self::SALESCHANNELGROUP_CONTEXT_KEY);
        if ($this->isRebalanceApplicable($product, $level, $group)) {
            $this->messageProducer->send(
                Topics::RESOLVE_REBALANCE_INVENTORY,
                ['product_id' => $product->getId(), 'jobId' => md5($product->getId())]
            );
        }
    }

    /**
     * Check if the rebalancing is applicable for the product
     * @param ProductInterface $product
     * @param BalancedInventoryLevelInterface $level
     * @param SalesChannelGroup $group
     * @return bool
     */
    protected function isRebalanceApplicable(
        ProductInterface $product,
        BalancedInventoryLevelInterface $level = null,
        SalesChannelGroup $group = null
    ) {
        if (!$level || !$group) {
            // cannot rebalance level without appropriate information to retrieve level
            return false;
        }

        if (!$level) {
            $level = $this->findExistingBalancedInventory($product, $group);
        }

        if (!$level) {
            //cannot update or calculate something when it's non-existent
            return false;
        }

        return $this->triggerCalculator->isBalanceThresholdReached($level);
    }

    /**
     * Find existing BalancedInventoryLevel
     * @param ProductInterface $product
     * @param SalesChannelGroup $group
     * @return BalancedInventoryRepository|object
     */
    protected function findExistingBalancedInventory(ProductInterface $product, SalesChannelGroup $group)
    {
        /** @var BalancedInventoryRepository $repository */
        return $this->repository->findExistingBalancedInventory($product, $group);
    }
}
