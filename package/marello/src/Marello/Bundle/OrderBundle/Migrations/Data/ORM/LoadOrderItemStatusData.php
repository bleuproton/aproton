<?php

namespace Marello\Bundle\OrderBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\EntityExtendBundle\Entity\Repository\EnumValueRepository;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

class LoadOrderItemStatusData extends AbstractFixture
{
    const ITEM_STATUS_ENUM_CLASS = 'marello_item_status';
    
    const PENDING = 'pending';
    const PROCESSING = 'processing';
    const SHIPPED = 'shipped';
    const DROPSHIPPED = 'dropshipped';
    const COULD_NOT_ALLOCATE = 'could_not_allocate';
    const WAITING_FOR_SUPPLY = 'waiting_for_supply';

    /** @var array */
    protected $data = [
        'Pending' => true,
        'Processing' => false,
        'Shipped' => false,
        'Dropshipped' => false,
        'Could Not Allocate' => false,
        'Waiting For Supply' => false,
    ];

    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $className = ExtendHelper::buildEnumValueClassName(self::ITEM_STATUS_ENUM_CLASS);

        /** @var EnumValueRepository $enumRepo */
        $enumRepo = $manager->getRepository($className);

        $priority = 1;
        foreach ($this->data as $name => $isDefault) {
            $enumOption = $enumRepo->createEnumValue($name, $priority++, $isDefault);
            $manager->persist($enumOption);
        }

        $manager->flush();
    }
}
