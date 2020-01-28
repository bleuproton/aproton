<?php

namespace MarelloEnterprise\Bundle\ReplenishmentBundle\Workflow;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Marello\Bundle\InventoryBundle\Entity\InventoryBatch;
use Marello\Bundle\InventoryBundle\Entity\InventoryItem;
use Marello\Bundle\InventoryBundle\Entity\Warehouse;
use Marello\Bundle\InventoryBundle\Event\InventoryUpdateEvent;
use Marello\Bundle\InventoryBundle\Model\InventoryUpdateContextFactory;
use MarelloEnterprise\Bundle\ReplenishmentBundle\Entity\ReplenishmentOrder;
use MarelloEnterprise\Bundle\ReplenishmentBundle\Entity\ReplenishmentOrderItem;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Component\ConfigExpression\ContextAccessor;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ReplenishmentOrderAllocateDestinationInventoryAction extends ReplenishmentOrderTransitionAction
{
    /**
     * @var Registry
     */
    protected $doctrine;

    /**
     * @param ContextAccessor           $contextAccessor
     * @param EventDispatcherInterface  $eventDispatcher
     * @param Registry                  $doctrine
     */
    public function __construct(
        ContextAccessor $contextAccessor,
        EventDispatcherInterface $eventDispatcher,
        Registry $doctrine
    ) {
        parent::__construct($contextAccessor, $eventDispatcher);

        $this->doctrine = $doctrine;
    }

    /**
     * @param WorkflowItem|mixed $context
     */
    protected function executeAction($context)
    {
        /** @var ReplenishmentOrder $order */
        $order = $context->getEntity();
        $originWarehouse = $order->getOrigin();
        $destinationWarehouse = $order->getDestination();
        $items = $order->getReplOrderItems();
        $items->map(function (ReplenishmentOrderItem $item) use ($order, $originWarehouse, $destinationWarehouse) {
            $this->handleInventoryUpdate(
                $item,
                $item->getInventoryQty(),
                0,
                $originWarehouse,
                $destinationWarehouse,
                $order
            );
        });
    }

    /**
     * handle the inventory update for items which have been shipped
     * @param ReplenishmentOrderItem $item
     * @param $inventoryUpdateQty
     * @param $allocatedInventoryQty
     * @param Warehouse $originWarehouse
     * @param Warehouse $destinationWarehouse
     * @param ReplenishmentOrder $order
     */
    protected function handleInventoryUpdate(
        $item,
        $inventoryUpdateQty,
        $allocatedInventoryQty,
        $originWarehouse,
        $destinationWarehouse,
        $order
    ) {
        $context = InventoryUpdateContextFactory::createInventoryUpdateContext(
            $item,
            null,
            $inventoryUpdateQty,
            $allocatedInventoryQty,
            'marelloenterprise.replenishment.replenishmentorder.workflow.completed',
            $order
        );
        if (!empty($item->getInventoryBatches())) {
            $contextBranches = [];
            foreach ($item->getInventoryBatches() as $batchNumber => $qty) {
                /** @var InventoryBatch[] $inventoryBatches */
                $inventoryBatches = $this->doctrine
                    ->getManagerForClass(InventoryBatch::class)
                    ->getRepository(InventoryBatch::class)
                    ->findBy(['batchNumber' => $batchNumber]);
                $originInventoryBatch = null;
                $destinationInventoryBatch = null;
                foreach ($inventoryBatches as $batch) {
                    $inventoryLevel = $batch->getInventoryLevel();
                    if ($inventoryLevel && $inventoryLevel->getWarehouse() === $originWarehouse) {
                        $originInventoryBatch = $batch;
                    }
                    if ($inventoryLevel && $inventoryLevel->getWarehouse() === $destinationWarehouse) {
                        $destinationInventoryBatch = $batch;
                    }
                }
                if ($originInventoryBatch && !$destinationInventoryBatch) {
                    /** @var InventoryItem $inventoryItem */
                    $inventoryItem = $item->getProduct()->getInventoryItems()->first();
                    if ($inventoryItem) {
                        $destinationInventoryLevel = $inventoryItem->getInventoryLevel($destinationWarehouse);
                        if ($destinationInventoryLevel) {
                            $destinationInventoryBatch = clone $originInventoryBatch;
                            $destinationInventoryBatch->setQuantity(0);
                            $destinationInventoryBatch->setInventoryLevel($destinationInventoryLevel);
                        }
                    }
                }
                if ($destinationInventoryBatch) {
                    $contextBranches[] = ['batch' => $destinationInventoryBatch, 'qty' => $qty];
                }
            }
            $context->setInventoryBatches($contextBranches);
        }
        $context->setValue('warehouse', $destinationWarehouse);

        $this->eventDispatcher->dispatch(
            InventoryUpdateEvent::NAME,
            new InventoryUpdateEvent($context)
        );
    }
}
