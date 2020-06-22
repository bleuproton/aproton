<?php

namespace MarelloEnterprise\Bundle\PurchaseOrderBundle\Tests\Unit\Form\Type;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Marello\Bundle\InventoryBundle\Entity\Warehouse;
use Marello\Bundle\PricingBundle\Form\Type\ProductPriceType;
use Marello\Bundle\ProductBundle\Entity\Product;
use Marello\Bundle\ProductBundle\Form\Type\ProductSupplierSelectType;
use Marello\Bundle\PurchaseOrderBundle\Entity\PurchaseOrder;
use Marello\Bundle\PurchaseOrderBundle\Form\Type\PurchaseOrderAdvisedItemCollectionType;
use Marello\Bundle\PurchaseOrderBundle\Form\Type\PurchaseOrderCreateStepTwoType;
use Marello\Bundle\PurchaseOrderBundle\Form\Type\PurchaseOrderItemCollectionType;
use Marello\Bundle\PurchaseOrderBundle\Form\Type\PurchaseOrderItemReceiveCollectionType;
use Marello\Bundle\PurchaseOrderBundle\Form\Type\PurchaseOrderItemType;
use Marello\Bundle\SupplierBundle\Entity\Supplier;
use Marello\Bundle\SupplierBundle\Form\Type\SupplierSelectType;
use MarelloEnterprise\Bundle\PurchaseOrderBundle\Form\Extension\PurchaseOrderWarehouseFormExtension;
use Oro\Bundle\CurrencyBundle\Tests\Unit\Utils\CurrencyNameHelperStub;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\FormBundle\Autocomplete\SearchRegistry;
use Oro\Bundle\FormBundle\Form\Type\CollectionType;
use Oro\Bundle\FormBundle\Form\Type\EntityIdentifierType;
use Oro\Bundle\FormBundle\Form\Type\MultipleEntityType;
use Oro\Bundle\FormBundle\Form\Type\OroDateType;
use Oro\Bundle\FormBundle\Form\Type\OroEntitySelectOrCreateInlineType;
use Oro\Bundle\FormBundle\Form\Type\OroMoneyType;
use Oro\Bundle\FormBundle\Tests\Unit\Form\Stub\EntityIdentifierType as EntityIdentifierStubType;
use Oro\Bundle\FormBundle\Tests\Unit\Form\Type\EntitySelectOrCreateInlineFormExtension;
use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Routing\Router;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\Validation;

class PurchaseOrderCreateStepTwoTypeTest extends FormIntegrationTestCase
{
    /**
     * @param array $submittedData
     * @param mixed $expectedData
     *
     * @dataProvider submitProvider
     */
    public function testSubmit($submittedData, $expectedData)
    {
        $form = $this->factory->create(
            PurchaseOrderCreateStepTwoType::class,
            new PurchaseOrder()
        );
        $form->submit($submittedData);
        $this->assertEquals($expectedData['isValid'], $form->isValid());
    }

    /**
     * @return array
     */
    public function submitProvider()
    {
        return [
            'valid' => [
                'submittedData' => [
                    'supplier' => 'supplier_1',
                    'dueDate' => '2018-05-16',
                    'itemsAdditional' => [
                        0 => [
                            'product' => 'product_1',
                            'orderedAmount' => 10,
                            'purchasePrice' => [
                                'currency' => 'USD',
                                'value' => 20.20
                            ]
                        ]
                    ],
                    'warehouse' => 'warehouse_1'
                ],
                'expectedData' => [
                    'isValid' => true
                ]
            ],
            'no_submitted_warehouse' => [
                'submittedData' => [
                    'supplier' => 'supplier_1',
                    'dueDate' => '2018-05-16',
                    'itemsAdditional' => [
                        0 => [
                            'product' => 'product_1',
                            'orderedAmount' => 10,
                            'purchasePrice' => [
                                'currency' => 'USD',
                                'value' => 20.20
                            ]
                        ]
                    ],
                ],
                'expectedData' => [
                    'isValid' => false,
                ]
            ],
            'not_existing_warehouse' => [
                'submittedData' => [
                    'supplier' => 'supplier_1',
                    'dueDate' => '2018-05-16',
                    'itemsAdditional' => [
                        0 => [
                            'product' => 'product_1',
                            'orderedAmount' => 10,
                            'purchasePrice' => [
                                'currency' => 'USD',
                                'value' => 20.20
                            ]
                        ]
                    ],
                    'warehouse' => 0
                ],
                'expectedData' => [
                    'isValid' => false
                ]
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getExtensions()
    {
        /** @var AuthorizationCheckerInterface|\PHPUnit_Framework_MockObject_MockObject $authorizationChecker */
        $authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        /** @var ConfigManager|\PHPUnit_Framework_MockObject_MockObject $configManager */
        $configManager = $this->getMockBuilder(ConfigManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var ConfigProvider|\PHPUnit_Framework_MockObject_MockObject $configProvider */
        $configProvider = $this->getMockBuilder(ConfigProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $configManager
            ->expects($this->any())
            ->method('getProvider')
            ->will($this->returnValue($configProvider));

        /** @var ConfigInterface|\PHPUnit_Framework_MockObject_MockObject $config */
        $config = $this->createMock(ConfigInterface::class);

        $configProvider
            ->expects($this->any())
            ->method('getConfig')
            ->will($this->returnValue($config));

        /** @var SearchRegistry|\PHPUnit_Framework_MockObject_MockObject $searchRegistry */
        $searchRegistry = $this->getMockBuilder(SearchRegistry::class)
            ->disableOriginalConstructor()
            ->setMethods(['hasSearchHandler', 'getSearchHandler'])
            ->getMock();

        $handler = $this->createMock('Oro\Bundle\FormBundle\Autocomplete\SearchHandlerInterface');
        $handler
            ->expects($this->any())
            ->method('getProperties')
            ->will($this->returnValue([]));

        $searchRegistry
            ->expects($this->any())
            ->method('getSearchHandler')
            ->will($this->returnValue($handler));

        $repository = $this->getMockBuilder(EntityRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $repository->expects($this->any())
            ->method('find')
            ->willReturnCallback(
                function ($id) {
                    if ($id === 'product_1') {
                        return new Product();
                    } elseif ($id === 'supplier_1') {
                        return new Supplier();
                    } elseif ($id === 'warehouse_1') {
                        return new Warehouse();
                    } else {
                        return null;
                    }
                }
            );
        /** @var EntityManager|\PHPUnit_Framework_MockObject_MockObject $entityManager */
        $entityManager = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $entityManager->expects($this->any())
            ->method('getRepository')
            ->willReturn($repository);

        $metadata = $this->getMockBuilder('\Doctrine\ORM\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();
        $metadata->expects($this->any())
            ->method('getSingleIdentifierFieldName')
            ->will($this->returnValue('id'));
        $entityManager->expects($this->any())
            ->method('getClassMetadata')
            ->will($this->returnValue($metadata));

        /** @var LocaleSettings|\PHPUnit_Framework_MockObject_MockObject $localeSettings */
        $localeSettings = $this->getMockBuilder('Oro\Bundle\LocaleBundle\Model\LocaleSettings')
            ->disableOriginalConstructor()
            ->setMethods(array('getLocale', 'getCurrency', 'getCurrencySymbolByCurrency'))
            ->getMock();

        /** @var NumberFormatter|\PHPUnit_Framework_MockObject_MockObject $numberFormatter */
        $numberFormatter = $this->getMockBuilder('Oro\Bundle\LocaleBundle\Formatter\NumberFormatter')
            ->disableOriginalConstructor()
            ->setMethods(array('isCurrencySymbolPrepend', 'getAttribute'))
            ->getMock();

        /** @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject $doctrineHelper */
        $doctrineHelper = $this->createMock(DoctrineHelper::class);

        /** @var Router|\PHPUnit_Framework_MockObject_MockObject $router */
        $router = $this->createMock(Router::class);

        return [
            new EntitySelectOrCreateInlineFormExtension(
                $entityManager,
                $searchRegistry,
                $configProvider
            ),
            new PreloadedExtension(
                [
                    OroEntitySelectOrCreateInlineType::class =>
                        new OroEntitySelectOrCreateInlineType(
                            $authorizationChecker,
                            $configManager,
                            $entityManager,
                            $searchRegistry
                        ),
                    SupplierSelectType::class => new SupplierSelectType(),
                    OroDateType::class => new OroDateType(),
                    MultipleEntityType::class => new MultipleEntityType($doctrineHelper, $authorizationChecker),
                    PurchaseOrderAdvisedItemCollectionType::class => new PurchaseOrderAdvisedItemCollectionType(),
                    EntityIdentifierType::class => new EntityIdentifierStubType([]),
                    PurchaseOrderItemCollectionType::class => new PurchaseOrderItemCollectionType(),
                    CollectionType::class => new CollectionType(),
                    PurchaseOrderItemType::class => new PurchaseOrderItemType(),
                    ProductSupplierSelectType::class => new ProductSupplierSelectType(),
                    ProductPriceType::class => new ProductPriceType(),
                    OroMoneyType::class => new OroMoneyType($localeSettings, $numberFormatter),
                    PurchaseOrderCreateStepTwoType::class =>
                        new PurchaseOrderCreateStepTwoType(
                            $router,
                            new CurrencyNameHelperStub()
                        )
                ],
                [
                    PurchaseOrderCreateStepTwoType::class => [new PurchaseOrderWarehouseFormExtension()]
                ]
            ),
            new ValidatorExtension(Validation::createValidator()),
        ];
    }
}
