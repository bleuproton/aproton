<?php

namespace Marello\Bundle\PaymentBundle\Tests\Unit\Provider\MethodsConfigsRule\Context\Basic;

use Oro\Bundle\LocaleBundle\Model\AddressInterface;
use Marello\Bundle\PaymentBundle\Entity\Repository\PaymentMethodsConfigsRuleRepository;
use Marello\Bundle\PaymentBundle\Provider\MethodsConfigsRule\Context\Basic\BasicMethodsConfigsRulesByContextProvider;
use Marello\Bundle\PaymentBundle\RuleFiltration\MethodsConfigsRulesFiltrationServiceInterface;
use Marello\Bundle\PaymentBundle\Tests\Unit\Context\PaymentContextMockTrait;
use Marello\Bundle\PaymentBundle\Tests\Unit\Entity\PaymentMethodsConfigsRuleMockTrait;

class BasicMethodsConfigsRulesByContextProviderTest extends \PHPUnit\Framework\TestCase
{
    use PaymentContextMockTrait;
    use PaymentMethodsConfigsRuleMockTrait;

    /**
     * @var PaymentMethodsConfigsRuleRepository|\PHPUnit\Framework\MockObject\MockObject
     */
    private $repository;

    /**
     * @var MethodsConfigsRulesFiltrationServiceInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $filtrationService;

    /**
     * @var BasicMethodsConfigsRulesByContextProvider
     */
    private $provider;

    protected function setUp()
    {
        $this->repository = $this->createMock(PaymentMethodsConfigsRuleRepository::class);

        $this->filtrationService = $this->createMock(MethodsConfigsRulesFiltrationServiceInterface::class);

        $this->provider = new BasicMethodsConfigsRulesByContextProvider(
            $this->filtrationService,
            $this->repository
        );
    }

    public function testGetAllFilteredPaymentMethodsConfigsWithBillingAddress()
    {
        $currency = 'USD';
        $address = $this->createAddressMock();
        $rulesFromDb = [
            $this->createPaymentMethodsConfigsRuleMock(),
            $this->createPaymentMethodsConfigsRuleMock(),
        ];

        $this->repository->expects(static::once())
            ->method('getByDestinationAndCurrency')
            ->with($address, $currency)
            ->willReturn($rulesFromDb);

        $this->repository->expects(static::never())
            ->method('getByCurrencyWithoutDestination');

        $context = $this->createPaymentContextMock();
        $context->method('getCurrency')
            ->willReturn($currency);
        $context->method('getBillingAddress')
            ->willReturn($address);

        $expectedRules = [$this->createPaymentMethodsConfigsRuleMock()];

        $this->filtrationService->expects(static::once())
            ->method('getFilteredPaymentMethodsConfigsRules')
            ->with($rulesFromDb)
            ->willReturn($expectedRules);

        static::assertSame(
            $expectedRules,
            $this->provider->getPaymentMethodsConfigsRules($context)
        );
    }

    public function testGetAllFilteredPaymentMethodsConfigsWithoutShippingAddress()
    {
        $currency = 'USD';
        $rulesFromDb = [$this->createPaymentMethodsConfigsRuleMock()];

        $this->repository->expects(static::once())
            ->method('getByCurrencyWithoutDestination')
            ->with($currency)
            ->willReturn($rulesFromDb);

        $context = $this->createPaymentContextMock();
        $context->method('getCurrency')
            ->willReturn($currency);

        $expectedRules = [$this->createPaymentMethodsConfigsRuleMock()];

        $this->filtrationService->expects(static::once())
            ->method('getFilteredPaymentMethodsConfigsRules')
            ->with($rulesFromDb)
            ->willReturn($expectedRules);

        static::assertSame(
            $expectedRules,
            $this->provider->getPaymentMethodsConfigsRules($context)
        );
    }

    /**
     * @return AddressInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private function createAddressMock()
    {
        return $this->createMock(AddressInterface::class);
    }
}
