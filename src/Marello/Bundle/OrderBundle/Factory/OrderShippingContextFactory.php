<?php

namespace Marello\Bundle\OrderBundle\Factory;

use Marello\Bundle\AddressBundle\Entity\MarelloAddress;
use Marello\Bundle\InventoryBundle\Model\OrderWarehouseResult;
use Marello\Bundle\InventoryBundle\Provider\OrderWarehousesProviderInterface;
use Marello\Bundle\OrderBundle\Converter\OrderShippingLineItemConverterInterface;
use Marello\Bundle\OrderBundle\Entity\Order;
use Marello\Bundle\OrderBundle\Entity\OrderItem;
use Marello\Bundle\OrderBundle\Event\OrderShippingContextBuildingEvent;
use Marello\Bundle\ShippingBundle\Context\Builder\Factory\ShippingContextBuilderFactoryInterface;
use Marello\Bundle\ShippingBundle\Context\ShippingContextFactoryInterface;
use Marello\Bundle\ShippingBundle\Context\ShippingContextInterface;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class OrderShippingContextFactory implements ShippingContextFactoryInterface
{
    /**
     * @var bool
     */
    private $estimation = false;
    
    /**
     * @var OrderWarehousesProviderInterface
     */
    private $orderWarehousesProvider;

    /**
     * @var OrderShippingLineItemConverterInterface
     */
    private $shippingLineItemConverter;

    /**
     * @var ShippingContextBuilderFactoryInterface|null
     */
    private $shippingContextBuilderFactory;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @param OrderWarehousesProviderInterface $orderWarehousesProvider
     * @param OrderShippingLineItemConverterInterface $shippingLineItemConverter
     * @param EventDispatcherInterface $eventDispatcher
     * @param null|ShippingContextBuilderFactoryInterface $shippingContextBuilderFactory
     */
    public function __construct(
        OrderWarehousesProviderInterface $orderWarehousesProvider,
        OrderShippingLineItemConverterInterface $shippingLineItemConverter,
        ShippingContextBuilderFactoryInterface $shippingContextBuilderFactory = null
    ) {
        $this->orderWarehousesProvider = $orderWarehousesProvider;
        $this->shippingLineItemConverter = $shippingLineItemConverter;
        $this->shippingContextBuilderFactory = $shippingContextBuilderFactory;
    }

    /**
     * @param Order $order
     * @return ShippingContextInterface[]
     */
    public function create($order)
    {
        $this->ensureApplicable($order);

        if (null === $this->shippingContextBuilderFactory) {
            return null;
        }

        $shippingContextBuilder = $this->shippingContextBuilderFactory->createShippingContextBuilder(
            $order,
            (string)$order->getId()
        );

        $results = [];
        $orderItems = $order->getItems();
        $subtotal = 0.00;
        /** @var OrderItem $orderItem */
        foreach ($orderItems as $orderItem) {
            $subtotal += $orderItem->getPrice() * $orderItem->getQuantity();
        }
        $subtotal = Price::create(
            $subtotal,
            $order->getCurrency()
        );

        $shippingContextBuilder
            ->setSubTotal($subtotal)
            ->setCurrency($order->getCurrency());

        $convertedLineItems = $this->shippingLineItemConverter->convertLineItems($orderItems);

        $this->orderWarehousesProvider->setEstimation($this->estimation);
        $shippingOrigin = $this->getShippingOrigin($order);
        if (null !== $shippingOrigin) {
            $shippingContextBuilder->setShippingOrigin($shippingOrigin);
        }
        if (null !== $order->getShippingAddress()) {
            $shippingContextBuilder->setShippingAddress($order->getShippingAddress());
        }

        if (null !== $order->getBillingAddress()) {
            $shippingContextBuilder->setBillingAddress($order->getBillingAddress());
        }

        if (null !== $order->getCustomer()) {
            $shippingContextBuilder->setCustomer($order->getCustomer());
        }

        if (null !== $convertedLineItems) {
            $shippingContextBuilder->setLineItems($convertedLineItems);
        }
        $shippingContext = $shippingContextBuilder->getResult();
        $event = new OrderShippingContextBuildingEvent($shippingContext);
        $this->eventDispatcher->dispatch(OrderShippingContextBuildingEvent::NAME, $event);
        $results[] = $event->getShippingContext();

        return $results;
    }

    /**
     * @param object $entity
     * @throws \InvalidArgumentException
     */
    protected function ensureApplicable($entity)
    {
        if (!is_a($entity, Order::class)) {
            throw new \InvalidArgumentException(sprintf(
                '"%s" expected, "%s" given',
                Order::class,
                is_object($entity) ? get_class($entity) : gettype($entity)
            ));
        }
    }

    /**
     * @param Order $order
     * @return MarelloAddress|null
     */
    protected function getShippingOrigin(Order $order)
    {
        $orderWarehouseResults = $this->orderWarehousesProvider->getWarehousesForOrder($order);
        if (!empty($orderWarehouseResults)) {
            /** @var OrderWarehouseResult $orderWarehouseResult */
            $orderWarehouseResult = reset($orderWarehouseResults);

            return $orderWarehouseResult->getWarehouse()->getAddress();
        }

        return null;
    }

    /**
     * {@inheritdoc}
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function setEventDispatcher(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * Set estimation for getting correct shipping methods when order on demand items are in the order
     * @param bool $estimation
     */
    public function setEstimation($estimation = false)
    {
        $this->estimation = $estimation;
    }

    /**
     * {@inheritdoc}
     * @return bool
     */
    public function isEstimation()
    {
        return $this->estimation;
    }
}
