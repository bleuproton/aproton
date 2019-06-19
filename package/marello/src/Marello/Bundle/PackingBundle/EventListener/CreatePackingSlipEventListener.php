<?php

namespace Marello\Bundle\PackingBundle\EventListener;

use Doctrine\Common\Persistence\ObjectManager;
use Marello\Bundle\OrderBundle\Entity\Order;
use Marello\Bundle\PackingBundle\Entity\PackingSlip;
use Marello\Bundle\PackingBundle\Event\BeforePackingSlipCreationEvent;
use Marello\Bundle\PackingBundle\Mapper\MapperInterface;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\WorkflowData;
use Oro\Component\Action\Event\ExtendableActionEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class CreatePackingSlipEventListener
{
    /**
     * @var MapperInterface
     */
    protected $mapper;

    /**
     * @var ObjectManager
     */
    protected $entityManager;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @var bool
     */
    protected $stopPropagation = false;

    /**
     * @param MapperInterface $mapper
     * @param DoctrineHelper $doctrineHelper
     * @param EventDispatcherInterface $eventDispatcher
     * @param bool $stopPropagation
     */
    public function __construct(
        MapperInterface $mapper,
        DoctrineHelper $doctrineHelper,
        EventDispatcherInterface $eventDispatcher,
        $stopPropagation = false
    ) {
        $this->mapper = $mapper;
        $this->entityManager = $doctrineHelper->getEntityManagerForClass(PackingSlip::class);
        $this->eventDispatcher = $eventDispatcher;
        $this->stopPropagation = $stopPropagation;
    }

    /**
     * @param ExtendableActionEvent $event
     * @throws \Exception
     */
    public function onCreatePackingSlip(ExtendableActionEvent $event)
    {
        if (!$this->isCorrectOrderContext($event->getContext())) {
            return;
        }

        /** @var Order $entity */
        $entity = $event->getContext()->getData()->get('order');
        $this->eventDispatcher
            ->dispatch(BeforePackingSlipCreationEvent::NAME, new BeforePackingSlipCreationEvent($entity));
        $packingSlips = $this->mapper->map($entity);

        if (0 === count($packingSlips)) {
            return;
        }
        foreach ($packingSlips as $packingSlip) {
            $this->entityManager->persist($packingSlip);
        }
        $this->entityManager->flush();
        
        if ($this->stopPropagation) {
            $event->stopPropagation();
        }
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
}
