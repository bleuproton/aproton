<?php

namespace Marello\Bundle\SalesBundle\Tests\Unit\EventListener\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Marello\Bundle\InventoryBundle\Entity\Repository\WarehouseChannelGroupLinkRepository;
use Marello\Bundle\InventoryBundle\Entity\WarehouseChannelGroupLink;
use Marello\Bundle\SalesBundle\Entity\Repository\SalesChannelGroupRepository;
use Marello\Bundle\SalesBundle\Entity\SalesChannel;
use Marello\Bundle\SalesBundle\Entity\SalesChannelGroup;
use Marello\Bundle\SalesBundle\EventListener\Doctrine\SalesChannelGroupListener;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Session\Session;

class SalesChannelGroupListenerTest extends TestCase
{
    /**
     * @var SalesChannelGroupListener
     */
    private $salesChannelGroupListener;

    /**
     * @var Session|\PHPUnit_Framework_MockObject_MockObject
     */
    private $session;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->session = $this->createMock(Session::class);
        $this->salesChannelGroupListener = new SalesChannelGroupListener(true, $this->session);
    }

    /**
     * @dataProvider preRemoveDataProvider
     * @param int $flushQty
     * @param int $persistQty
     * @param MockObject|null $systemSalesChannelGroup
     * @param MockObject|null $systemLink
     */
    public function testPreRemove(
        $flushQty,
        $persistQty,
        MockObject $systemSalesChannelGroup = null,
        MockObject $systemLink = null
    ) {
        /** @var SalesChannel|\PHPUnit_Framework_MockObject_MockObject $salesChannel **/
        $salesChannel = $this->createMock(SalesChannel::class);
        $salesChannel
            ->expects(static::exactly($persistQty))
            ->method('setGroup')
            ->with($systemSalesChannelGroup);
        /** @var SalesChannelGroup|\PHPUnit_Framework_MockObject_MockObject $salesChannelGroup **/
        $salesChannelGroup = $this->createMock(SalesChannelGroup::class);
        $salesChannelGroup
            ->expects(static::exactly($persistQty))
            ->method('getSalesChannels')
            ->willReturn([$salesChannel]);

        $groupRepository = $this->createMock(SalesChannelGroupRepository::class);
        $groupRepository
            ->expects(static::once())
            ->method('findSystemChannelGroup')
            ->willReturn($systemSalesChannelGroup);
        $linkRepository = $this->createMock(WarehouseChannelGroupLinkRepository::class);
        $linkRepository
            ->expects(static::once())
            ->method('findSystemLink')
            ->willReturn($systemLink);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects(static::exactly(2))
            ->method('getRepository')
            ->withConsecutive(
                [SalesChannelGroup::class],
                [WarehouseChannelGroupLink::class]
            )
            ->willReturnOnConsecutiveCalls(
                $groupRepository,
                $linkRepository
            );
        $entityManager
            ->expects(static::exactly($persistQty))
            ->method('persist')
            ->with($salesChannel);
        $entityManager
            ->expects(static::exactly($flushQty))
            ->method('flush');

        /** @var LifecycleEventArgs|\PHPUnit_Framework_MockObject_MockObject $args **/
        $args = $this->getMockBuilder(LifecycleEventArgs::class)
            ->disableOriginalConstructor()
            ->getMock();
        $args
            ->expects(static::once())
            ->method('getEntityManager')
            ->willReturn($entityManager);
        $args
            ->expects(static::once())
            ->method('getEntity')
            ->willReturn($salesChannelGroup);

        $this->salesChannelGroupListener->preRemove($args);
    }
    
    /**
     * @return array
     */
    public function preRemoveDataProvider()
    {
        return [
            'withSystemGroupWithSystemLink' => [
                'flushQty' => 1,
                'persistQty' => 1,
                'group' => $this->createMock(SalesChannelGroup::class),
                'link' => $this->createMock(WarehouseChannelGroupLink::class)
            ],
            'noSystemGroupWithSystemLink' => [
                'flushQty' => 1,
                'persistQty' => 0,
                'group' => null,
                'link' => $this->createMock(WarehouseChannelGroupLink::class)
            ],
            'withSystemGroupNoSystemLink' => [
                'flushQty' => 1,
                'persistQty' => 1,
                'group' => $this->createMock(SalesChannelGroup::class),
                'link' => null
            ],
            'noSystemGroupNoSystemLink' => [
                'flushQty' => 0,
                'persistQty' => 0,
                'group' => null,
                'link' => null
            ]
        ];
    }

    /**
     * @dataProvider postPersistDataProvider
     * @param int $qty
     * @param MockObject|null $defaultLink
     */
    public function testPostPersist($qty, MockObject $defaultLink = null)
    {
        /** @var SalesChannelGroup|MockObject $salesChannelGroup **/
        $salesChannelGroup = $this->createMock(SalesChannelGroup::class);

        $repository = $this->createMock(WarehouseChannelGroupLinkRepository::class);
        $repository
            ->expects(static::once())
            ->method('findSystemLink')
            ->willReturn($defaultLink);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects(static::once())
            ->method('getRepository')
            ->with(WarehouseChannelGroupLink::class)
            ->willReturn($repository);
        $entityManager
            ->expects(static::exactly($qty))
            ->method('persist')
            ->with($defaultLink);
        $entityManager
            ->expects(static::exactly($qty))
            ->method('flush');

        /** @var LifecycleEventArgs|\PHPUnit_Framework_MockObject_MockObject $args **/
        $args = $this->getMockBuilder(LifecycleEventArgs::class)
            ->disableOriginalConstructor()
            ->getMock();
        $args
            ->expects(static::once())
            ->method('getEntityManager')
            ->willReturn($entityManager);

        $this->salesChannelGroupListener->postPersist($salesChannelGroup, $args);
    }

    /**
     * @return array
     */
    public function postPersistDataProvider()
    {
        return [
            'withDefaultLink' => [
                'qty' => 1,
                'link' => $this->createMock(WarehouseChannelGroupLink::class)
            ],
            'noDefaultLink' => [
                'qty' => 0,
                'link' => null
            ]
        ];
    }
}
