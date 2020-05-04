<?php

namespace Marello\Bundle\MagentoBundle\Tests\Unit\Form\Type;

use Oro\Bundle\IntegrationBundle\Manager\TypesRegistry;
use Marello\Bundle\MagentoBundle\Entity\MagentoSoapTransport;
use Marello\Bundle\MagentoBundle\Form\EventListener\ConnectorsFormSubscriber;
use Marello\Bundle\MagentoBundle\Form\EventListener\SettingsFormSubscriber;
use Marello\Bundle\MagentoBundle\Form\EventListener\SharedEmailListSubscriber;
use Marello\Bundle\MagentoBundle\Form\EventListener\IsDisplayOrderNotesSubscriber;
use Marello\Bundle\MagentoBundle\Form\Type\SoapTransportSettingFormType;
use Marello\Bundle\MagentoBundle\Provider\Transport\SoapTransport;

class SoapTransportSettingFormTypeTest extends \PHPUnit_Framework_TestCase
{
    /** @var  SoapTransport | \PHPUnit_Framework_MockObject_MockObject */
    protected $soapTransport;

    /** @var  SettingsFormSubscriber | \PHPUnit_Framework_MockObject_MockObject */
    protected $settingsFormSubscriber;

    /** @var  TypesRegistry| \PHPUnit_Framework_MockObject_MockObject */
    protected $typesRegistry;

    /** @var  SoapTransportSettingFormType */
    protected $type;

    protected function setUp()
    {
        $this->soapTransport = $this->getMockBuilder(SoapTransport::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->settingsFormSubscriber = $this->getMockBuilder(SettingsFormSubscriber::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->typesRegistry = $this->getMockBuilder(TypesRegistry::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->type = new SoapTransportSettingFormType(
            $this->soapTransport,
            $this->settingsFormSubscriber,
            $this->typesRegistry
        );
    }

    protected function tearDown()
    {
        unset($this->type);
    }

    public function testBuildForm()
    {
        $builder = $this->getMockBuilder('Symfony\Component\Form\FormBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $builder->expects($this->exactly(3))
            ->method('addEventSubscriber')
            ->with($this->logicalOr(
                $this->isInstanceOf(SettingsFormSubscriber::class),
                $this->isInstanceOf(ConnectorsFormSubscriber::class),
                $this->isInstanceOf(IsDisplayOrderNotesSubscriber::class)
            ))->willReturnSelf();

        $builder->expects($this->any())
            ->method('add')
            ->willReturn($builder);

        $builder->expects($this->exactly(4))
            ->method('create')
            ->willReturn($builder);

        $builder->expects($this->exactly(1))
            ->method('addViewTransformer')
            ->willReturn($builder);

        $this->type->buildForm($builder, []);
    }

    public function testSetDefaultOptions()
    {
        $resolver = $this->createMock('Symfony\Component\OptionsResolver\OptionsResolverInterface');
        $this->soapTransport->expects($this->once())
            ->method('getSettingsEntityFQCN')
            ->willReturn(MagentoSoapTransport::class);
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with(
                [
                    'data_class' => MagentoSoapTransport::class,
                ]
            );

        $this->type->setDefaultOptions($resolver);
    }

    public function testGetName()
    {
        $this->assertEquals(SoapTransportSettingFormType::NAME, $this->type->getName());
    }
}
