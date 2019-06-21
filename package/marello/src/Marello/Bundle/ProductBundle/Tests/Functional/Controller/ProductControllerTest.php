<?php

namespace Marello\Bundle\ProductBundle\Tests\Functional\Controller;

use Marello\Bundle\ProductBundle\Entity\Product;
use Marello\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Marello\Bundle\SalesBundle\Tests\Functional\DataFixtures\LoadSalesData;
use Marello\Bundle\SupplierBundle\Tests\Functional\DataFixtures\LoadSupplierData;
use Marello\Bundle\TaxBundle\Tests\Functional\DataFixtures\LoadTaxCodeData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Form;
use Symfony\Component\HttpFoundation\Response;

/**
 * @outputBuffering enabled
 */
class ProductControllerTest extends WebTestCase
{
    const GRID_NAME = 'marello-products-grid';

    public function setUp()
    {
        $this->initClient(
            [],
            array_merge($this->generateBasicAuthHeader(), ['HTTP_X-CSRF-Header' => 1])
        );

        $this->loadFixtures([
            LoadProductData::class
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function testIndex()
    {
        $this->client->request('GET', $this->getUrl('marello_product_index'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, Response::HTTP_OK);
    }

    /**
     * {@inheritdoc}
     */
    public function testCreateProduct()
    {
        $productTypesProvider = $this->getContainer()->get('marello_product.provider.product_types');
        $crawler = $this->client->request('GET', $this->getUrl('marello_product_create'));
        $form    = $crawler->selectButton('Continue')->form();
        $formValues = $form->getPhpValues();
        $formValues['input_action'] = 'marello_product_create';
        $formValues['marello_product_step_one']['type'] = $productTypesProvider->getProductType('simple')->getName();
        $this->client->followRedirects(true);
        $crawler = $this->client->request(
            'POST',
            $this->getUrl('marello_product_create'),
            $formValues
        );

        $name    = 'Super duper product';
        $sku     = 'SKU-1234';

        $form    = $crawler->selectButton('Save and Close')->form();
        $formValues = $form->getPhpValues();
        $formValues['marello_product_form']['name'] = $name;
        $formValues['marello_product_form']['sku'] = $sku;
        $formValues['marello_product_form']['status'] = 'enabled';
        $formValues['marello_product_form']['addSalesChannels']
            = $this->getReference(LoadSalesData::CHANNEL_1_REF)->getId();
        $formValues['marello_product_form']['taxCode']
            = $this->getReference(LoadTaxCodeData::TAXCODE_3_REF)->getId();

        $this->client->followRedirects(true);
        $crawler = $this->client->request($form->getMethod(), $form->getUri(), $formValues);
        $result  = $this->client->getResponse();

        self::assertHtmlResponseStatusCodeEquals($result, Response::HTTP_OK);
        $this->assertContains('Product saved', $crawler->html());
        $this->assertContains($name, $crawler->html());

        return $name;
    }

    /**
     * {@inheritdoc}
     */
    public function testUpdateProductSuppliers()
    {
        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_1_REF);

        $crawler = $this->client->request(
            'GET',
            $this->getUrl('marello_product_update', ['id' => $product->getId()])
        );

        $result  = $this->client->getResponse();
        self::assertHtmlResponseStatusCodeEquals($result, Response::HTTP_OK);

        /** @var Form $form */
        $form = $crawler->selectButton('Save')->form();

        $this->assertTrue($product->hasSuppliers());

        $productSuppliers = [
            [
                'supplier' => $this->getReference(LoadSupplierData::SUPPLIER_1_REF)->getId(),
                'quantityOfUnit' => 24,
                'priority' => 2,
                'cost' => 33.55,
                'canDropship' => true
            ],
            [
                'supplier' => $this->getReference(LoadSupplierData::SUPPLIER_1_REF)->getId(),
                'quantityOfUnit' => 48,
                'priority' => 3,
                'cost' => 42.50,
                'canDropship' => true
            ],
            [
                'supplier' => $this->getReference(LoadSupplierData::SUPPLIER_2_REF)->getId(),
                'quantityOfUnit' => 100,
                'priority' => 1,
                'cost' => 60.99,
                'canDropship' => false
            ]
        ];

        $submittedData = [
            'input_action' => 'save_and_stay',
            'marello_product_form' => [
                '_token' => $form['marello_product_form[_token]']->getValue(),
                'name' => $form['marello_product_form[name]']->getValue(),
                'sku' => $form['marello_product_form[sku]']->getValue(),
                'status' => $form['marello_product_form[status]']->getValue(),
                'suppliers' => $productSuppliers
            ]
        ];

        $this->client->followRedirects(true);

        // Submit form
        $result = $this->client->getResponse();
        $this->client->request($form->getMethod(), $form->getUri(), $submittedData);
        self::assertHtmlResponseStatusCodeEquals($result, Response::HTTP_OK);
    }

    /**
     * {@inheritdoc}
     */
    public function testUpdateProductTaxCodes()
    {
        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_1_REF);

        $crawler = $this->client->request(
            'GET',
            $this->getUrl('marello_product_update', ['id' => $product->getId()])
        );

        $result  = $this->client->getResponse();
        self::assertHtmlResponseStatusCodeEquals($result, Response::HTTP_OK);

        /** @var Form $form */
        $form = $crawler->selectButton('Save')->form();

        $this->assertTrue($product->hasChannels());

        $taxCode = $this->getReference(LoadTaxCodeData::TAXCODE_1_REF)->getId();
        $salesChannelTaxCodes = [
            [
                'taxCode' => $this->getReference(LoadTaxCodeData::TAXCODE_2_REF)->getId(),
                'salesChannel' => $product->getChannels()->first()->getId(),
            ]
        ];

        $submittedData = [
            'input_action' => 'save_and_stay',
            'marello_product_form' => [
                '_token' => $form['marello_product_form[_token]']->getValue(),
                'name' => $form['marello_product_form[name]']->getValue(),
                'sku' => $form['marello_product_form[sku]']->getValue(),
                'status' => $form['marello_product_form[status]']->getValue(),
                'taxCode' => $taxCode,
                'salesChannelTaxCodes' => $salesChannelTaxCodes,
            ]
        ];

        $this->client->followRedirects(true);

        // Submit form
        $result = $this->client->getResponse();
        $this->client->request($form->getMethod(), $form->getUri(), $submittedData);
        self::assertHtmlResponseStatusCodeEquals($result, Response::HTTP_OK);
    }

    /**
     * @param string $name
     *
     * @depends testCreateProduct
     *
     * @return string
     */
    public function testUpdateProduct($name)
    {
        $response = $this->client->requestGrid(
            'marello-products-grid',
            ['marello-products-grid[_filter][name][value]' => $name]
        );

        $result = self::getJsonResponseContent($response, Response::HTTP_OK);
        $result = reset($result['data']);

        $resultData = $result;
        $crawler     = $this->client->request(
            'GET',
            $this->getUrl('marello_product_update', ['id' => $result['id']])
        );
        $result      = $this->client->getResponse();
        self::assertHtmlResponseStatusCodeEquals($result, Response::HTTP_OK);

        $taxCode = $this->getReference(LoadTaxCodeData::TAXCODE_3_REF)->getId();
        /** @var Form $form */
        $form                                              = $crawler->selectButton('Save and Close')->form();
        $name                                              = 'name' . self::generateRandomString();
        $form['marello_product_form[name]']                = $name;
        $form['marello_product_form[removeSalesChannels]']
            = $this->getReference(LoadSalesData::CHANNEL_1_REF)->getId();
        $form['marello_product_form[addSalesChannels]']
            = $this->getReference(LoadSalesData::CHANNEL_2_REF)->getId();
        $form['marello_product_form[taxCode]']             = $taxCode;

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        self::assertHtmlResponseStatusCodeEquals($result, Response::HTTP_OK);
        self::assertContains('Product saved', $crawler->html());

        $resultData['name'] = $name;

        return $resultData;
    }

    /**
     * @param array $resultData
     * @depends testUpdateProduct
     */
    public function testProductView($resultData)
    {
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('marello_product_view', ['id' => $resultData['id']])
        );

        $result = $this->client->getResponse();
        self::assertHtmlResponseStatusCodeEquals($result, Response::HTTP_OK);
        self::assertContains(
            (string) $this->getReference(LoadSalesData::CHANNEL_2_REF)->getName(),
            $crawler->html()
        );
        self::assertContains((string) $resultData['name'], $crawler->html());
    }

    /**
     * @param array $resultData
     * @depends testUpdateProduct
     */
    public function testProductInfo($resultData)
    {
        $crawler = $this->client->request(
            'GET',
            $this->getUrl(
                'marello_product_widget_info',
                ['id' => $resultData['id'], '_widgetContainer' => 'block']
            )
        );

        $result = $this->client->getResponse();
        self::assertHtmlResponseStatusCodeEquals($result, Response::HTTP_OK);
        self::assertContains($resultData['name'], $crawler->html());
    }
}
