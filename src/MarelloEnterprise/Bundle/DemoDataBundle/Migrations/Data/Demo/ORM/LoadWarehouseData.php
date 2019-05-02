<?php

namespace MarelloEnterprise\Bundle\DemoDataBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Marello\Bundle\AddressBundle\Entity\MarelloAddress;
use Marello\Bundle\InventoryBundle\Entity\Warehouse;
use Marello\Bundle\InventoryBundle\Entity\WarehouseGroup;
use Marello\Bundle\InventoryBundle\Entity\WarehouseType;
use Marello\Bundle\InventoryBundle\Migrations\Data\ORM\LoadWarehouseData as BaseWarehouseData;
use Marello\Bundle\InventoryBundle\Provider\WarehouseTypeProviderInterface;
use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

class LoadWarehouseData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @var ObjectManager
     */
    protected $manager;

    /**
     * @var Organization
     */
    protected $organization;
    
    /**
     * @var WarehouseGroup
     */
    protected $systemGroup;

    /**
     * @var array
     */
    protected $data = [
        'current_default' => [
            'default'   => true,
            'type'      => 'global',
            'group'         => 'Europe'
        ],
        'warehouse_de_2' => [
            'name'          => 'Warehouse DE 2',
            'code'          => 'warehouse_de_2',
            'default'       => false,
            'address'       => [
                'country' => 'DE',
                'street' => 'Platz der Luftbrücke 5',
                'city' => 'Berlin',
                'state' => 'BE',
                'postalCode' => '12101',
                'phone' => '000-000-000',
                'company' => 'Goodwaves Berlin'
            ],
            'type'          => 'global',
            'group'         => 'Europe'
        ],
        'warehouse_fr_1' => [
            'name'          => 'Warehouse FR 1',
            'code'          => 'warehouse_fr_1',
            'default'       => false,
            'address'       => [
                'country' => 'FR',
                'street' => '22 Av. des Champs-Élysées',
                'city' => 'Paris',
                'state' => '75',
                'postalCode' => '75008',
                'phone' => '000-000-000',
                'company' => 'Goodwaves Paris'
            ],
            'type'          => 'global',
            'group'         => 'Europe'
        ],
        'warehouse_fr_2' => [
            'name'          => 'Warehouse FR 2',
            'code'          => 'warehouse_fr_2',
            'default'       => false,
            'address'       => [
                'country' => 'FR',
                'street' => '120 Cours de la Marne',
                'city' => 'Bordeaux',
                'state' => '33',
                'postalCode' => '33800',
                'phone' => '000-000-000',
                'company' => 'Goodwaves Bordeaux'
            ],
            'type'          => 'global',
            'group'         => 'Europe'
        ],
        'warehouse_us_1' => [
            'name'          => 'Warehouse US 1',
            'code'          => 'warehouse_us_1',
            'default'       => false,
            'address'       => [
                'country' => 'US',
                'street' => 'Rusk 2714',
                'city' => 'Tucson',
                'state' => 'AZ',
                'postalCode' => '58742',
                'phone' => '520-921-7115',
                'company' => null
            ],
            'type'          => 'global',
            'group'         => 'US'
        ],
        'warehouse_de_munchen' => [
            'name'          => 'Store Warehouse DE München',
            'code'          => 'store_warehouse_de_munchen',
            'default'       => false,
            'address'       => [
                'country' => 'DE',
                'street' => 'Nordallee 25',
                'city' => 'München',
                'state' => 'BY',
                'postalCode' => '85356',
                'phone' => '000-000-000',
                'company' => 'Goodwaves München'
            ],
            'type'          => 'fixed',
            'group'         => 'Store Warehouse DE München'
        ],
        'warehouse_de_frankfurt' => [
            'name'          => 'Store Warehouse DE Frankfurt',
            'code'          => 'store_warehouse_de_frankfurt',
            'default'       => false,
            'address'       => [
                'country' => 'DE',
                'street' => 'Flughafen Frankfurt am Main 200',
                'city' => 'Frankfurt am Main',
                'state' => 'HE',
                'postalCode' => '60549',
                'phone' => '000-000-000',
                'company' => 'Goodwaves Frankfurt'
            ],
            'type'          => 'fixed',
            'group'         => 'Store Warehouse DE Frankfurt'
        ],
        'warehouse_de_berlin' => [
            'name'          => 'Store Warehouse DE Berlin',
            'code'          => 'store_warehouse_de_berlin',
            'default'       => false,
            'address'       => [
                'country' => 'DE',
                'street' => 'Grunerstraße 20',
                'city' => 'Berlin',
                'state' => 'BE',
                'postalCode' => '10179',
                'phone' => '000-000-000',
                'company' => 'Goodwaves Berlin'
            ],
            'type'          => 'fixed',
            'group'         => 'Store Warehouse DE Berlin'
        ],
        'warehouse_de_dortmund' => [
            'name'          => 'Store Warehouse DE Dortmund',
            'code'          => 'store_warehouse_de_dortmund',
            'default'       => false,
            'address'       => [
                'country' => 'DE',
                'street' => 'Straße der Pariser Kommune 23',
                'city' => 'Dortmund',
                'state' => 'NW',
                'postalCode' => '44369',
                'phone' => '0231 41 39356',
                'company' => 'Goodwaves Dortmund'
            ],
            'type'          => 'fixed',
            'group'         => 'Store Warehouse DE Dortmund'
        ],
        'warehouse_uk_1' => [
            'name'          => 'Warehouse UK 1',
            'code'          => 'warehouse_uk_1',
            'default'       => false,
            'address'       => [
                'country' => 'GB',
                'street' => '71 Harehills Lane',
                'city' => 'Rowton',
                'state' => 'CHW',
                'postalCode' => 'TF6 4AA',
                'phone' => '079 6390 9378',
                'company' => 'Goodwaves Rowton'
            ],
            'type'          => 'global',
            'group'         =>  null
        ],
        'warehouse_uk_2' => [
            'name'          => 'Warehouse UK 2',
            'code'          => 'warehouse_uk_2',
            'default'       => false,
            'address'       => [
                'country' => 'GB',
                'street' => '49 St James Boulevard',
                'city' => 'Hornby',
                'state' => 'NYK',
                'postalCode' => 'LA2 9HE',
                'phone' => '077 5352 6738',
                'company' => 'Goodwaves Hornby'
            ],
            'type'          => 'global',
            'group'         =>  null
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            BaseWarehouseData::class
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $this->manager = $manager;
        $this->organization = $this->getOrganization();
        $this->systemGroup = $this->getSystemWarehouseGroup();

        $this->loadWarehouses();
    }

    /**
     * Get organization
     * @return Organization
     */
    protected function getOrganization()
    {
        return $this->manager->getRepository('OroOrganizationBundle:Organization')->getFirst();
    }

    /**
     * @return WarehouseGroup
     */
    protected function getSystemWarehouseGroup()
    {
        return $this->manager->getRepository('MarelloInventoryBundle:WarehouseGroup')->findOneBy(['system' => true]);
    }

    /**
     * load Warehouses
     */
    public function loadWarehouses()
    {
        foreach ($this->data as $warehouseKey => $data) {
            $this->createWarehouse($data);
        }
        
        $this->manager->flush();
    }

    /**
     * Create new Warehouse
     * @param array $data
     * @return Warehouse $warehouse
     */
    private function createWarehouse(array $data)
    {
        if ($data['default'] === true) {
            $warehouse = $this->manager
                ->getRepository('MarelloInventoryBundle:Warehouse')
                ->getDefault();
        } else {
            $warehouse = new Warehouse($data['name'], false);
            $warehouse->setOwner($this->organization);
            $warehouse->setCode($data['code']);

            $address = $this->createAddress($data['address']);
            $warehouse->setAddress($address);

            $this->manager->persist($warehouse);
        }

        $type = $this->getWarehouseType($data['type']);
        $warehouse->setWarehouseType($type);
        $this->loadWarehouseGroups($warehouse, $data);

        return $warehouse;
    }

    /**
     * Load additional warehouse groups
     * @param Warehouse $warehouse
     * @param array $data
     */
    private function loadWarehouseGroups(Warehouse $warehouse, array $data)
    {
        $group = null;
        if ($warehouse->getWarehouseType()->getName() === WarehouseTypeProviderInterface::WAREHOUSE_TYPE_FIXED) {
            $group = $this->createNewWarehouseGroup($warehouse, $data['group']);
        } elseif (isset($data['group'])) {
            $existingGroup = $this->getExistingWarehouseGroup($data['group']);
            if ($existingGroup) {
                $group = $existingGroup;
            } else {
                $group = $this->createNewWarehouseGroup($warehouse, $data['group']);
            }
        } else {
            $group = $this->systemGroup;
        }

        if ($group) {
            $warehouse->setGroup($group);
            $this->setReference(sprintf('warehouse.%s', $data['group']), $group);
        }
    }

    /**
     * Create new WarehouseGroup
     * @param Warehouse $warehouse
     * @param null $groupName
     * @return WarehouseGroup
     */
    private function createNewWarehouseGroup(Warehouse $warehouse, $groupName = null)
    {
        $description = $groupName = $groupName ? $groupName : $warehouse->getLabel();
        $group = new WarehouseGroup();
        $group
            ->setName($groupName)
            ->setOrganization($warehouse->getOwner())
            ->setDescription($description)
            ->setSystem(false);

        $this->manager->persist($group);
        $this->manager->flush();

        return $group;
    }

    /**
     * Get Warehouse GroupName
     * @param $warehouseGroupName
     * @return WarehouseType
     */
    private function getExistingWarehouseGroup($warehouseGroupName)
    {
        return $this->manager->getRepository(WarehouseGroup::class)->findOneBy(['name' => $warehouseGroupName]);
    }

    /**
     * Get Warehouse Type
     * @param $type
     * @return WarehouseType
     */
    private function getWarehouseType($type)
    {
        return $this->manager->getRepository(WarehouseType::class)->findOneBy(['name' => $type]);
    }

    /**
     * Create Address from dummy data
     * @param array $data
     * @return MarelloAddress
     */
    private function createAddress(array $data)
    {
        $warehouseAddress = new MarelloAddress();
        $warehouseAddress->setStreet($data['street']);
        $warehouseAddress->setPostalCode($data['postalCode']);
        $warehouseAddress->setCity($data['city']);
        /** @var Country $country */
        $country = $this->manager->getRepository('OroAddressBundle:Country')->find($data['country']);
        $warehouseAddress->setCountry($country);
        /** @var Region $region */
        $region = $this->manager
            ->getRepository('OroAddressBundle:Region')
            ->findOneBy(['combinedCode' => $data['country'] . '-' . $data['state']]);
        $warehouseAddress->setRegion($region);
        $warehouseAddress->setPhone($data['phone']);
        $warehouseAddress->setCompany($data['company']);
        $this->manager->persist($warehouseAddress);
        
        return $warehouseAddress;
    }
}
