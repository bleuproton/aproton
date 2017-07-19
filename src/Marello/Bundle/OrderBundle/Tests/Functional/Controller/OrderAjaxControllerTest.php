<?php

namespace Marello\Bundle\OrderBundle\Tests\Functional\Controller;

use Marello\Bundle\OrderBundle\Form\Type\OrderType;
use Marello\Bundle\OrderBundle\Provider\OrderItem\OrderItemDataProviderInterface;
use Marello\Bundle\OrderBundle\Provider\OrderItemFormChangesProvider;
use Marello\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrderData;
use Marello\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Marello\Bundle\SalesBundle\Tests\Functional\DataFixtures\LoadSalesData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolation
 */
class OrderAjaxControllerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures([
            LoadOrderData::class,
        ]);
    }

    public function testFormChangesAction()
    {
        $orderItemKeys = ['price', 'tax_code'];
        $productIds = [
            $this->getReference(LoadProductData::PRODUCT_1_REF)->getId(),
            $this->getReference(LoadProductData::PRODUCT_2_REF)->getId(),
            $this->getReference(LoadProductData::PRODUCT_3_REF)->getId()
        ];
        $this->client->request(
            'POST',
            $this->getUrl('marello_order_form_changes'),
            [
                OrderType::NAME => [
                    'salesChannel' => $this->getReference(LoadSalesData::CHANNEL_1_REF)->getId(),
                    'items' => [
                        ['product' => $productIds[0]],
                        ['product' => $productIds[1]],
                        ['product' => $productIds[2]],
                    ]
                ]
            ]
        );

        $response = $this->client->getResponse();
        $this->assertInstanceOf('Symfony\Component\HttpFoundation\JsonResponse', $response);

        $result = $this->getJsonResponseContent($response, 200);
        $this->assertArrayHasKey(OrderItemFormChangesProvider::ITEMS_FIELD, $result);
        $this->assertCount(count($productIds), $result[OrderItemFormChangesProvider::ITEMS_FIELD]);
        foreach ($productIds as $id) {
            $this->assertArrayHasKey($this->getIdentifier($id), $result[OrderItemFormChangesProvider::ITEMS_FIELD]);
            foreach ($orderItemKeys as $key) {
                $this->assertArrayHasKey(
                    $key,
                    $result[OrderItemFormChangesProvider::ITEMS_FIELD][$this->getIdentifier($id)]
                );
            }
        }
    }

    /**
     * @param int $productId
     * @return string
     */
    protected function getIdentifier($productId)
    {
        return sprintf('%s%s', OrderItemDataProviderInterface::IDENTIFIER_PREFIX, $productId);
    }
}
