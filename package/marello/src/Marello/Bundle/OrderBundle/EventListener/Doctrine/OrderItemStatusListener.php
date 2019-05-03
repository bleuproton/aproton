<?php

namespace Marello\Bundle\OrderBundle\EventListener\Doctrine;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Marello\Bundle\InventoryBundle\Entity\InventoryItem;
use Marello\Bundle\InventoryBundle\Provider\AvailableInventoryProvider;
use Marello\Bundle\OrderBundle\Entity\Order;
use Marello\Bundle\OrderBundle\Entity\OrderItem;
use Marello\Bundle\OrderBundle\Migrations\Data\ORM\LoadOrderItemStatusData;
use Marello\Bundle\PackingBundle\Entity\PackingSlipItem;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\WorkflowData;
use Oro\Component\Action\Event\ExtendableActionEvent;

class OrderItemStatusListener
{
    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;
    
    /**
     * @var AvailableInventoryProvider
     */
    protected $availableInventoryProvider;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param AvailableInventoryProvider $availableInventoryProvider
     */
    public function __construct(DoctrineHelper $doctrineHelper, AvailableInventoryProvider $availableInventoryProvider)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->availableInventoryProvider = $availableInventoryProvider;
    }


    /**
     * @param LifecycleEventArgs $args
     */
    public function postPersist(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();
        if ($entity instanceof OrderItem) {
            $product = $entity->getProduct();
            /** @var InventoryItem $inventoryItem */
            $inventoryItem = $product->getInventoryItems()->first();
            $availableInventory = $this->availableInventoryProvider->getAvailableInventory(
                $product,
                $entity->getOrder()->getSalesChannel()
            );
            if ($availableInventory < $entity->getQuantity() &&
                (
                    ($inventoryItem->isBackorderAllowed() &&
                        $inventoryItem->getMaxQtyToBackorder() >= $entity->getQuantity()
                    ) || $inventoryItem->isCanPreorder()
                )
            ) {
                $entity->setStatus($this->findStatusByName(LoadOrderItemStatusData::WAITING_FOR_SUPPLY));
            } else {
                $entity->setStatus($this->findDefaultStatus());
            }
        }
        if ($entity instanceof PackingSlipItem) {
            $orderItem = $entity->getOrderItem();
            $orderItem->setStatus($entity->getStatus());
        }
    }

    /**
     * @param ExtendableActionEvent $event
     */
    public function onOrderPaid(ExtendableActionEvent $event)
    {
        if (!$this->isCorrectOrderContext($event->getContext())) {
            return;
        }
        $entityManager = $this->doctrineHelper->getEntityManagerForClass(OrderItem::class);
        /** @var Order $entity */
        $entity = $event->getContext()->getData()->get('order');
        foreach ($entity->getItems() as $orderItem) {
            $orderItem->setStatus($this->findStatusByName(LoadOrderItemStatusData::PROCESSING));
            $entityManager->persist($orderItem);
        }
        $entityManager->flush();
    }
    
    /**
     * @param ExtendableActionEvent $event
     */
    public function onOrderShipped(ExtendableActionEvent $event)
    {
        if (!$this->isCorrectOrderContext($event->getContext())) {
            return;
        }
        $entityManager = $this->doctrineHelper->getEntityManagerForClass(OrderItem::class);
        /** @var Order $entity */
        $entity = $event->getContext()->getData()->get('order');
        foreach ($entity->getItems() as $orderItem) {
            $orderItem->setStatus($this->findStatusByName(LoadOrderItemStatusData::SHIPPED));
            $entityManager->persist($orderItem);
        }
        $entityManager->flush();
    }

    /**
     * @param mixed $context
     * @return bool
     */
    protected function isCorrectOrderContext($context)
    {
        return ($context instanceof WorkflowItem
            && $context->getData() instanceof WorkflowData
            && $context->getData()->has('order')
            && $context->getData()->get('order') instanceof Order
        );
    }
    
    /**
     * @return null|object
     */
    private function findDefaultStatus()
    {
        $statusClass = ExtendHelper::buildEnumValueClassName(LoadOrderItemStatusData::ITEM_STATUS_ENUM_CLASS);
        $status = $this->doctrineHelper
            ->getEntityManagerForClass($statusClass)
            ->getRepository($statusClass)
            ->findOneByDefault(true);

        if ($status) {
            return $status;
        }

        return null;
    }
    
    /**
     * @param string $name
     * @return null|object
     */
    private function findStatusByName($name)
    {
        $statusClass = ExtendHelper::buildEnumValueClassName(LoadOrderItemStatusData::ITEM_STATUS_ENUM_CLASS);
        $status = $this->doctrineHelper
            ->getEntityManagerForClass($statusClass)
            ->getRepository($statusClass)
            ->find($name);

        if ($status) {
            return $status;
        }

        return null;
    }
}
