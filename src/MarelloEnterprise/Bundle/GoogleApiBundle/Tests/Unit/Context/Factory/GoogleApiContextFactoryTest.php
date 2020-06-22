<?php

namespace MarelloEnterprise\Bundle\GoogleApiBundle\Tests\Unit\Context\Factory;

use PHPUnit\Framework\TestCase;

use Marello\Bundle\AddressBundle\Entity\MarelloAddress;
use MarelloEnterprise\Bundle\GoogleApiBundle\Context\GoogleApiContext;
use MarelloEnterprise\Bundle\GoogleApiBundle\Context\Factory\GoogleApiContextFactory;

class GoogleApiContextFactoryTest extends TestCase
{
    /**
     * @var GoogleApiContextFactory
     */
    protected $googleApiContextFactory;

    protected function setUp()
    {
        $this->googleApiContextFactory = new GoogleApiContextFactory();
    }

    public function testCreateContext()
    {
        /** @var MarelloAddress|\PHPUnit_Framework_MockObject_MockObject $originAddress **/
        $originAddress = $this->createMock(MarelloAddress::class);

        /** @var MarelloAddress|\PHPUnit_Framework_MockObject_MockObject $destinationAddress **/
        $destinationAddress = $this->createMock(MarelloAddress::class);

        $expectedContext = new GoogleApiContext([
            GoogleApiContext::FIELD_ORIGIN_ADDRESS => $originAddress,
            GoogleApiContext::FIELD_DESTINATION_ADDRESS => $destinationAddress
        ]);

        $actualContext = $this->googleApiContextFactory->createContext($originAddress, $destinationAddress);

        static::assertEquals($expectedContext, $actualContext);
    }
}
