<?php

namespace MarelloEnterprise\Bundle\InventoryBundle\Tests\Unit\EventListener\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Marello\Bundle\InventoryBundle\Entity\Repository\WarehouseGroupRepository;
use Marello\Bundle\InventoryBundle\Entity\Warehouse;
use Marello\Bundle\InventoryBundle\Entity\WarehouseGroup;
use MarelloEnterprise\Bundle\InventoryBundle\EventListener\Doctrine\WarehouseListener;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Translation\TranslatorInterface;

class WarehouseListenerTest extends TestCase
{
    /**
     * @var WarehouseListener
     */
    private $warehouseListener;

    /**
     * @var TranslatorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $translator;

    /**
     * @var Session|\PHPUnit_Framework_MockObject_MockObject
     */
    private $session;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->session = $this->createMock(Session::class);
        $this->warehouseListener = new WarehouseListener(true, $this->translator, $this->session);
    }

    /**
     * @dataProvider prePersistDataProvider
     * @param \PHPUnit_Framework_MockObject_MockObject|null $warehouseGroup
     */
    public function testPrePersist(\PHPUnit_Framework_MockObject_MockObject $warehouseGroup = null)
    {
        $warehouse = new Warehouse();

        $repository = $this->createMock(WarehouseGroupRepository::class);
        $repository
            ->expects(static::once())
            ->method('findSystemWarehouseGroup')
            ->willReturn($warehouseGroup);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects(static::once())
            ->method('getRepository')
            ->with(WarehouseGroup::class)
            ->willReturn($repository);

        /** @var LifecycleEventArgs|\PHPUnit_Framework_MockObject_MockObject $args **/
        $args = $this
            ->getMockBuilder(LifecycleEventArgs::class)
            ->disableOriginalConstructor()
            ->getMock();
        $args
            ->expects(static::once())
            ->method('getEntityManager')
            ->willReturn($entityManager);

        $this->warehouseListener->prePersist($warehouse, $args);

        static::assertEquals($warehouseGroup, $warehouse->getGroup());
    }

    /**
     * @return array
     */
    public function prePersistDataProvider()
    {
        return [
            'withSystemGroup' => [
                'group' => $this->createMock(WarehouseGroup::class),
            ],
            'noSystemGroup' => [
                'group' => null,
            ]
        ];
    }
}
