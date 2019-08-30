<?php

namespace Marello\Bundle\PdfBundle\Tests\Unit\Provider;

use Marello\Bundle\InvoiceBundle\Entity\Invoice;
use Marello\Bundle\PdfBundle\Lib\View\Table;
use Marello\Bundle\PdfBundle\Provider\DocumentTableProvider;
use Marello\Bundle\PdfBundle\Provider\TableProviderInterface;
use PHPUnit\Framework\TestCase;

class DocumentTableProviderTest extends TestCase
{
    protected $provider;

    public function setUp()
    {
        $this->provider = new DocumentTableProvider();
    }

    public function testGetTablesSupported()
    {
        /** @var TableProviderInterface|\PHPUnit_Framework_MockObject_MockObject $workingProvider */
        $workingProvider = $this->createMock(TableProviderInterface::class);
        $workingProvider->expects($this->once())
            ->method('supports')
            ->willReturn(true)
        ;
        $workingProvider->expects($this->once())
            ->method('getTables')
            ->willReturn(new Table(20, 5, 4))
        ;

        /** @var TableProviderInterface|\PHPUnit_Framework_MockObject_MockObject $notWorkingProvider */
        $notWorkingProvider = $this->createMock(TableProviderInterface::class);
        $notWorkingProvider->expects($this->once())
            ->method('supports')
            ->willReturn(false)
        ;

        $this->provider->addProvider($notWorkingProvider);
        $this->provider->addProvider($workingProvider);

        $tables = $this->provider->getTables(new Invoice());

        $this->assertNotEmpty($tables);
    }

    public function testGetTablesNotSupported()
    {
        /** @var TableProviderInterface|\PHPUnit_Framework_MockObject_MockObject $notWorkingProvider */
        $notWorkingProvider = $this->createMock(TableProviderInterface::class);
        $notWorkingProvider->expects($this->once())
            ->method('supports')
            ->willReturn(false)
        ;

        $this->provider->addProvider($notWorkingProvider);

        $tables = $this->provider->getTables(new Invoice());

        $this->assertEmpty($tables);
    }
}
