<?php

namespace Marello\Bundle\OrderBundle\Tests\Functional\Controller;

use Marello\Bundle\AddressBundle\Entity\MarelloAddress;
use Marello\Bundle\OrderBundle\Entity\Customer;
use Marello\Bundle\OrderBundle\Entity\Order;
use Marello\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrderData;
use Marello\Bundle\PackingBundle\Entity\PackingSlip;
use Marello\Bundle\PackingBundle\Entity\PackingSlipItem;
use Marello\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Marello\Bundle\ProductBundle\Entity\Product;
use Marello\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Marello\Bundle\PurchaseOrderBundle\Entity\PurchaseOrder;
use Marello\Bundle\PurchaseOrderBundle\Entity\PurchaseOrderItem;
use Marello\Bundle\PurchaseOrderBundle\Workflow\Action\ReceivePurchaseOrderAction;
use Marello\Bundle\SalesBundle\Entity\SalesChannel;
use Marello\Bundle\SalesBundle\Tests\Functional\DataFixtures\LoadSalesData;
use Marello\Bundle\ShippingBundle\Entity\Shipment;
use Marello\Bundle\ShippingBundle\Method\ShippingMethodInterface;
use Marello\Bundle\ShippingBundle\Method\ShippingMethodProviderInterface;
use Marello\Bundle\ShippingBundle\Method\ShippingMethodTypeInterface;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Form;
use Symfony\Component\HttpFoundation\Response;

class OrderOnDemandWorkflowTest extends WebTestCase
{
    public function setUp()
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader()
        );

        $this->loadFixtures([
            LoadOrderData::class,
        ]);
    }

    /**
     * @return int
     */
    public function testWorkflow()
    {
        $crawler = $this->client->request('GET', $this->getUrl('marello_order_order_create'));
        $result  = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        /** @var Form $form */
        $form = $crawler->selectButton('Save and Close')->form();

        /** @var Customer $orderCustomer */
        $orderCustomer = $this->getReference('marello-customer-1');

        /** @var SalesChannel $salesChannel */
        $salesChannel = $this->getReference(LoadSalesData::CHANNEL_1_REF);

        /** @var Product $product */
        $product1 = $this->getReference(LoadProductData::PRODUCT_1_REF);
        $price1 = $product1->getSalesChannelPrice($salesChannel)->getPrice()->getValue();
        /** @var Product $product */
        $product6 = $this->getReference(LoadProductData::PRODUCT_6_REF);
        $price6 = $product6->getSalesChannelPrice($salesChannel)->getPrice()->getValue();
        
        $orderItems = [
            [
                'product' => $product1->getId(),
                'quantity' => 1,
                'availableInventory' => 1,
                'price' => $price1,
                'tax' => 0.00,
                'taxCode' => $product1->getTaxCode()->getCode(),
                'rowTotalExclTax' => $price1,
                'rowTotalInclTax' => $price1
            ],
            [
                'product' => $product6->getId(),
                'quantity' => 1,
                'availableInventory' => 0,
                'price' => $price6,
                'tax' => 0.00,
                'taxCode' => $product6->getTaxCode()->getCode(),
                'rowTotalExclTax' => $price6,
                'rowTotalInclTax' => $price6
            ],
        ];
        $submittedData = $this->getSubmittedData($form, $orderCustomer, $salesChannel, $orderItems);

        $beforePurchaseOrders = $this->getContainer()->get('doctrine')
            ->getManagerForClass(PurchaseOrder::class)
            ->getRepository(PurchaseOrder::class)
            ->findAll();
        $this->assertEmpty($beforePurchaseOrders);

        $this->client->followRedirects(true);

        $this->client->request($form->getMethod(), $form->getUri(), $submittedData);
        $result  = $this->client->getResponse();

        $this->assertResponseStatusCodeEquals($result, Response::HTTP_OK);

        /** @var Order $order */
        $order = $this->getContainer()->get('doctrine')
            ->getManagerForClass('MarelloOrderBundle:Order')
            ->getRepository('MarelloOrderBundle:Order')
            ->findOneBy([
                'customer' => $orderCustomer->getId(),
                'salesChannel' => $salesChannel->getId(),
                'subtotal' => $price1 + $price6
            ]);
        $this->assertNotEmpty($order);

        $orderItem1 = $order->getItems()[0];
        static::assertSame($product1->getSku(), $orderItem1->getProductSku());
        $orderItem2 = $order->getItems()[1];
        static::assertSame($product6->getSku(), $orderItem2->getProductSku());

        $afterPurchaseOrders = $this->getContainer()->get('doctrine')
            ->getManagerForClass(PurchaseOrder::class)
            ->getRepository(PurchaseOrder::class)
            ->findAll();
        $this->assertCount(1, $afterPurchaseOrders);
        /** @var PurchaseOrder $purchaseOrder */
        $purchaseOrder = reset($afterPurchaseOrders);
        $this->assertCount(1, $purchaseOrder->getItems());
        /** @var PurchaseOrderItem $poItem */
        $poItem = $purchaseOrder->getItems()->first();
        static::assertSame($product6->getSku(), $poItem->getProductSku());
        static::assertSame($orderItem2->getQuantity(), $poItem->getOrderedAmount());

        $doctrine = $this->getContainer()->get('doctrine');
        $beforeShipment = $doctrine
            ->getManagerForClass(Shipment::class)
            ->getRepository(Shipment::class)
            ->findAll();
        $this->assertEmpty($beforeShipment);
        $beforePackingSlips = $doctrine
            ->getManagerForClass(PackingSlip::class)
            ->getRepository(PackingSlip::class)
            ->findAll();
        $this->assertEmpty($beforePackingSlips);

        $workflowManager = $this->getContainer()->get('oro_workflow.manager');
        $orderWorkflowItem = $workflowManager->getWorkflowItem($order, 'marello_order_b2c_workflow_1');
        $workflowManager->transit($orderWorkflowItem, 'payment_received');
        $workflowManager->transit($orderWorkflowItem, 'invoice');
        $workflowManager->transit($orderWorkflowItem, 'prepare_shipping');
        $workflowManager->transit($orderWorkflowItem, 'ship');

        $afterShipment = $doctrine
            ->getManagerForClass(Shipment::class)
            ->getRepository(Shipment::class)
            ->findAll();
        $this->assertCount(1, $afterShipment);
        $afterPackingSlips = $doctrine
            ->getManagerForClass(PackingSlip::class)
            ->getRepository(PackingSlip::class)
            ->findAll();
        $this->assertCount(1, $afterPackingSlips);
        /** @var PackingSlip $packingSlip */
        $packingSlip = reset($afterPackingSlips);
        $this->assertCount(1, $packingSlip->getItems());
        /** @var PackingSlipItem $packingSlipItem */
        $packingSlipItem = $packingSlip->getItems()->first();
        static::assertSame($packingSlipItem->getProductSku(), $orderItem1->getProductSku());

        $poWorkflowItem = $workflowManager->getWorkflowItem($purchaseOrder, 'marello_purchase_order_workflow');
        $data = $poWorkflowItem->getData();
        $poItem
            ->setReceivedAmount(1)
            ->setData([ReceivePurchaseOrderAction::LAST_PARTIALLY_RECEIVED_QTY => 1]);
        $data->set('received_items', $purchaseOrder);
        $poWorkflowItem->setData($data);
        $workflowManager->transit($poWorkflowItem, 'partially_receive');

        $afterPoShipment = $doctrine
            ->getManagerForClass(Shipment::class)
            ->getRepository(Shipment::class)
            ->findAll();
        $this->assertCount(2, $afterPoShipment);
        $afterPoPackingSlips = $doctrine
            ->getManagerForClass(PackingSlip::class)
            ->getRepository(PackingSlip::class)
            ->findAll();
        $this->assertCount(2, $afterPoPackingSlips);
        
        return $order->getId();
    }

    /**
     * @param Form $form
     * @param Customer $orderCustomer
     * @param SalesChannel $salesChannel
     * @param $orderItems
     * @return array
     */
    private function getSubmittedData($form, $orderCustomer, $salesChannel, $orderItems)
    {
        $paymentMethodProvider = $this->getContainer()->get('marello_payment.payment_method.composite_provider');
        $paymentMethods = $paymentMethodProvider->getPaymentMethods();
        /** @var PaymentMethodInterface $paymentMethod */
        $paymentMethod = reset($paymentMethods);
        /** @var ShippingMethodProviderInterface $shippingMethodsProvider */
        $shippingMethodsProvider = $this->getContainer()->get('marello_shipping.shipping_method_provider');
        $shippingMethods = $shippingMethodsProvider->getShippingMethods();
        /** @var ShippingMethodInterface $shippingMethod */
        $shippingMethod = reset($shippingMethods);
        $shippingMethodTypes = $shippingMethod->getTypes();
        /** @var ShippingMethodTypeInterface $shippingMethodType */
        $shippingMethodType = reset($shippingMethodTypes);
        $submittedData = [
            'input_action' => 'save_and_stay',
            'marello_order_order' => [
                '_token' => $form['marello_order_order[_token]']->getValue(),
                'customer' => $orderCustomer->getId(),
                'salesChannel' => $salesChannel->getId(),
                'items' => $orderItems,
                'billingAddress' => $this->getAddressFormData($orderCustomer->getPrimaryAddress()),
                'shippingAddress' => $this->getAddressFormData($orderCustomer->getPrimaryAddress()),
                'calculateShipping' => true,
                'paymentMethod' => $paymentMethod->getIdentifier(),
                'shippingMethod' => $shippingMethod->getIdentifier(),
                'shippingMethodType' => $shippingMethodType->getIdentifier(),
                'estimatedShippingCostAmount' => 5.00
            ]
        ];

        return $submittedData;
    }

    /**
     * @param MarelloAddress $address
     * @return array
     */
    private function getAddressFormData(MarelloAddress $address)
    {
        return [
            'namePrefix' => $address->getNamePrefix(),
            'firstName' => $address->getFirstName(),
            'middleName' => $address->getMiddleName(),
            'lastName' => $address->getLastName(),
            'nameSuffix' => $address->getNameSuffix(),
            'country' => $address->getCountryIso2(),
            'street' => $address->getStreet(),
            'street2' => $address->getStreet2(),
            'city' => $address->getCity(),
            'region' => $address->getRegionCode(),
            'region_text' => $address->getRegionText(),
            'postalCode' => $address->getPostalCode(),
            'phone' =>$address->getPhone(),
            'company' => $address->getCompany()
        ];
    }
}
