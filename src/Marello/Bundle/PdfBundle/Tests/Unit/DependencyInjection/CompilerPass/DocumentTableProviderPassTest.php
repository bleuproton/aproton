<?php

namespace Marello\Bundle\PdfBundle\Tests\Unit\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\ContainerBuilder;

use PHPUnit\Framework\TestCase;

use Marello\Bundle\PdfBundle\DependencyInjection\CompilerPass\DocumentTableProviderPass;

class DocumentTableProviderPassTest extends TestCase
{
    /**
     * @var DocumentTableProviderPass
     */
    protected $compilerPass;

    /**
     * @var ContainerBuilder|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $containerBuilder;

    public function setUp()
    {
        $this->containerBuilder = $this
            ->getMockBuilder(ContainerBuilder::class)
            ->getMock();

        $this->compilerPass = new DocumentTableProviderPass();
    }

    public function testProcessRegistryDoesNotExist()
    {
        $this->containerBuilder
            ->expects($this->once())
            ->method('hasDefinition')
            ->with(DocumentTableProviderPass::PROVIDER_SERVICE_ID)
            ->willReturn(false)
        ;

        $this->containerBuilder
            ->expects($this->never())
            ->method('getDefinition')
        ;

        $this->containerBuilder
            ->expects($this->never())
            ->method('findTaggedServiceIds')
        ;

        $this->compilerPass->process($this->containerBuilder);
    }

    public function testProcessNoTaggedServicesFound()
    {
        $this->containerBuilder
            ->expects($this->once())
            ->method('hasDefinition')
            ->with(DocumentTableProviderPass::PROVIDER_SERVICE_ID)
            ->willReturn(true)
        ;

        $this->containerBuilder
            ->expects($this->once())
            ->method('findTaggedServiceIds')
            ->with(DocumentTableProviderPass::TAG_NAME)
            ->willReturn([])
        ;

        $this->containerBuilder
            ->expects($this->once())
            ->method('getDefinition')
            ->with(DocumentTableProviderPass::PROVIDER_SERVICE_ID)
        ;

        $this->compilerPass->process($this->containerBuilder);
    }

    public function testProcessWithTaggedServices()
    {
        $this->containerBuilder
            ->expects($this->once())
            ->method('hasDefinition')
            ->with(DocumentTableProviderPass::PROVIDER_SERVICE_ID)
            ->willReturn(true);

        $registryServiceDefinition = $this->createMock(Definition::class);

        $this->containerBuilder
            ->expects($this->once())
            ->method('getDefinition')
            ->with(DocumentTableProviderPass::PROVIDER_SERVICE_ID)
            ->willReturn($registryServiceDefinition);

        $taggedServices = [
            'service.name.1' => [[]],
            'service.name.2' => [[]],
            'service.name.3' => [[]],
            'service.name.4' => [[]],
        ];

        $this->containerBuilder
            ->expects($this->once())
            ->method('findTaggedServiceIds')
            ->with(DocumentTableProviderPass::TAG_NAME)
            ->willReturn($taggedServices);

        $registryServiceDefinition
            ->expects($this->exactly(4))
            ->method('addMethodCall')
            ->withConsecutive(
                ['addProvider', [new Reference('service.name.1')]],
                ['addProvider', [new Reference('service.name.2')]],
                ['addProvider', [new Reference('service.name.3')]],
                ['addProvider', [new Reference('service.name.4')]]
            );

        $this->compilerPass->process($this->containerBuilder);
    }
}
