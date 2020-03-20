<?php

namespace MarelloEnterprise\Bundle\InventoryBundle\Tests\Unit\Form\Handler;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ObjectManager;

use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ParameterBag;

use PHPUnit\Framework\TestCase;

use Marello\Bundle\InventoryBundle\Entity\Warehouse;
use Marello\Bundle\InventoryBundle\Entity\WarehouseGroup;
use Marello\Bundle\InventoryBundle\Entity\Repository\WarehouseGroupRepository;
use MarelloEnterprise\Bundle\InventoryBundle\Form\Handler\WarehouseGroupHandler;

class WarehouseGroupHandlerTest extends TestCase
{
    /**
     * @var FormInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $form;

    /**
     * @var ObjectManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $manager;

    /**
     * @var WarehouseGroupHandler
     */
    protected $warehouseGroupHandler;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->form = $this->createMock(FormInterface::class);
        $this->manager = $this->createMock(ObjectManager::class);
        $this->warehouseGroupHandler = new WarehouseGroupHandler($this->manager);
    }

    public function testProcess()
    {
        /** @var WarehouseGroup|\PHPUnit_Framework_MockObject_MockObject $updatedGroup */
        $updatedGroup = $this->createMock(WarehouseGroup::class);
        /** @var WarehouseGroup|\PHPUnit_Framework_MockObject_MockObject $systemGroup */
        $systemGroup = $this->createMock(WarehouseGroup::class);

        $wh1 = $this->mockWarehouse($systemGroup);
        $wh2 = $this->mockWarehouse($updatedGroup);
        $wh3 = $this->mockWarehouse($updatedGroup);

        $whBefore = [$wh1, $wh2];
        $whAfter = [$wh2, $wh3];

        $updatedGroup
            ->expects(static::at(0))
            ->method('getWarehouses')
            ->willReturn(new ArrayCollection($whBefore));
        $updatedGroup
            ->expects(static::at(1))
            ->method('getWarehouses')
            ->willReturn(new ArrayCollection($whAfter));

        $repository = $this->createMock(WarehouseGroupRepository::class);
        $repository
            ->expects(static::once())
            ->method('findSystemWarehouseGroup')
            ->willReturn($systemGroup);

        $this->manager
            ->expects(static::once())
            ->method('getRepository')
            ->with(WarehouseGroup::class)
            ->willReturn($repository);

        $this->manager
            ->expects(static::exactly(4))
            ->method('persist')
            ->withConsecutive(
                [$wh1],
                [$wh2],
                [$wh3],
                [$updatedGroup]
            );
        $this->manager
            ->expects(static::once())
            ->method('flush');

        /** @var Request|\PHPUnit_Framework_MockObject_MockObject $request */
        $request = $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->getMock();
        $request
            ->expects(static::once())
            ->method('getMethod')
            ->willReturn('POST');
        $request->request = new ParameterBag([]);
        $request->files = new ParameterBag([]);
        $this->form
            ->expects(static::once())
            ->method('setData')
            ->with($updatedGroup);
        $this->form
            ->expects(static::once())
            ->method('submit')
            ->with([]);
        $this->form
            ->expects(static::once())
            ->method('isValid')
            ->willReturn(true);

        $this->warehouseGroupHandler->process($updatedGroup, $this->form, $request);
    }

    /**
     * @param MockObject $group
     * @return MockObject
     */
    private function mockWarehouse(MockObject $group)
    {
        $salesChannel = $this->createMock(Warehouse::class);
        $salesChannel
            ->expects(static::once())
            ->method('setGroup')
            ->with($group);

        return $salesChannel;
    }
}
