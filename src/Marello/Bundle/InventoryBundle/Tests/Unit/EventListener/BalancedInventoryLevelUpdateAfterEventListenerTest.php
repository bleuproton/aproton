<?php

namespace Marello\Bundle\InventoryBundle\Tests\Unit\EventListener;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;

use PHPUnit\Framework\TestCase;

use Marello\Bundle\ProductBundle\Entity\Product;
use Marello\Bundle\SalesBundle\Entity\SalesChannelGroup;
use Marello\Bundle\InventoryBundle\Manager\InventoryManager;
use Marello\Bundle\InventoryBundle\Model\InventoryUpdateContext;
use Marello\Bundle\InventoryBundle\Event\BalancedInventoryUpdateEvent;
use Marello\Bundle\InventoryBundle\Model\BalancedInventoryLevelInterface;
use Marello\Bundle\InventoryBundle\Entity\Repository\BalancedInventoryRepository;
use Marello\Bundle\InventoryBundle\EventListener\BalancedInventoryUpdateAfterEventListener;
use Marello\Bundle\InventoryBundle\Model\InventoryBalancer\InventoryBalancerTriggerCalculator;

class BalancedInventoryLevelUpdateAfterEventListenerTest extends TestCase
{
    /**
     * @var InventoryUpdateContext|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $inventoryUpdateContext;

    /**
     * @var BalancedInventoryUpdateAfterEventListener $listener
     */
    protected $listener;

    /**
     * @var InventoryManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $inventoryManager;

    /**
     * @var MessageProducerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $producer;

    /**
     * @var BalancedInventoryRepository
     */
    protected $repository;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->inventoryManager = $this->createMock(InventoryManager::class);

        $this->producer = $this->createMock(MessageProducerInterface::class);
        /** @var ConfigManager|\PHPUnit_Framework_MockObject_MockObject $configManager */
        $configManager =  $this->createMock(ConfigManager::class);
        $calculator = new InventoryBalancerTriggerCalculator($configManager);

        $this->repository =  $this->createMock(BalancedInventoryRepository::class);

        $this->listener = new BalancedInventoryUpdateAfterEventListener(
            $this->producer,
            $calculator,
            $this->repository
        );
    }

    /**
     * Test that the event is not handled because the context is for a virtual inventory level
     */
    public function testEventIsNotHandledDuringWrongContext()
    {
        $context = new InventoryUpdateContext();
        $event = $this->prepareEvent($context);

        $result = $this->listener->handleInventoryUpdateAfterEvent($event);
        $this->assertNull($result);
    }

    /**
     * Test that the event is not handled because the context is for an inventory level
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage To few arguments given in the context,
        no balancedInventoryLevel or salesChannelGroup given, please check your data
     */
    public function testThrowInvalidArgumentExceptionOnToFewDataGivenInContext()
    {
        $context = new InventoryUpdateContext();
        $context->setIsVirtual(true);
        $event = $this->prepareEvent($context);

        $this->listener->handleInventoryUpdateAfterEvent($event);
    }

    public function testRebalanceThresholdHasBeenReachedAndTriggerIsBeingSend()
    {
        $context = new InventoryUpdateContext();
        /** @var BalancedInventoryLevelInterface|\PHPUnit_Framework_MockObject_MockObject $level */
        $level = $this->createMock(BalancedInventoryLevelInterface::class);

        $context->setValue(BalancedInventoryUpdateAfterEventListener::BALANCED_LEVEL_CONTEXT_KEY, $level);
        $context->setValue(
            BalancedInventoryUpdateAfterEventListener::SALESCHANNELGROUP_CONTEXT_KEY,
            $this->createMock(SalesChannelGroup::class)
        );
        $context->setProduct($this->createMock(Product::class));
        $context->setIsVirtual(true);
        $event = $this->prepareEvent($context);

        /** @var InventoryBalancerTriggerCalculator|\PHPUnit_Framework_MockObject_MockObject $calculator */
        $calculator = $this->createMock(InventoryBalancerTriggerCalculator::class);

        $calculator
            ->expects($this->once())
            ->method('isBalanceThresholdReached')
            ->with($this->createMock(BalancedInventoryLevelInterface::class))
            ->willReturn(true);

        $listener = new BalancedInventoryUpdateAfterEventListener(
            $this->producer,
            $calculator,
            $this->repository
        );

        $listener->handleInventoryUpdateAfterEvent($event);
    }

    /**
     * @param InventoryUpdateContext $context
     * @return BalancedInventoryUpdateEvent
     */
    protected function prepareEvent(InventoryUpdateContext $context)
    {
        return new BalancedInventoryUpdateEvent($context);
    }
}
