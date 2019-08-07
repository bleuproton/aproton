<?php

namespace Marello\Bundle\CustomerBundle\Tests\Functional\Controller;

use Marello\Bundle\AddressBundle\Entity\MarelloAddress;
use Marello\Bundle\AddressBundle\Tests\Functional\Api\DataFixtures\LoadAddressData;
use Marello\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCompanyData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Form;
use Symfony\Component\HttpFoundation\Response;

/**
 * @outputBuffering enabled
 */
class CompanyControllerTest extends WebTestCase
{
    const GRID_NAME = 'marello-companies-grid';

    public function setUp()
    {
        $this->initClient(
            [],
            array_merge($this->generateBasicAuthHeader(), ['HTTP_X-CSRF-Header' => 1])
        );

        $this->loadFixtures([
            LoadAddressData::class,
            LoadCompanyData::class
        ]);
    }

    /**
     * Test getting grid without errors
     */
    public function testCompaniesIndex()
    {
        $this->client->request('GET', $this->getUrl('marello_customer_company_index'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, Response::HTTP_OK);

        $response = $this->client->requestGrid(self::GRID_NAME);
        $result = $this->getJsonResponseContent($response, Response::HTTP_OK);
        $this->assertCount(3, $result);
    }

    /**
     * {@inheritdoc}
     */
    public function testCompanyCreate()
    {
        $crawler = $this->client->request('GET', $this->getUrl('marello_customer_company_create'));
        $name    = 'Company 1';
        $form    = $crawler->selectButton('Save and Close')->form();
        $customer = $this->getReference('marello-customer-7');
        $parent = $this->getReference(LoadCompanyData::COMPANY_3_REF);

        $form['marello_customer_company[name]'] = $name;
        $form['marello_customer_company[parent]'] = $parent->getId();
        $form['marello_customer_company[appendCustomers]'] = $customer->getId();
        $form['marello_customer_company[addresses]'] = [
            $this->getAddressFormData($this->getReference(LoadAddressData::ADDRESS_2_REF))
        ];

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);
        $result  = $this->client->getResponse();

        $this->assertHtmlResponseStatusCodeEquals($result, Response::HTTP_OK);
        $this->assertContains('Company has been saved', $crawler->html());

        $response = $this->client->requestGrid(self::GRID_NAME);
        self::assertJsonResponseStatusCodeEquals($response, Response::HTTP_OK);
        $this->assertContains($name, $response->getContent());

        return $name;
    }

    /**
     * @param string $name
     *
     * @depends testCompanyCreate
     *
     * @return string
     */
    public function testCompanyUpdate($name)
    {
        $response = $this->client->requestGrid(
            self::GRID_NAME,
            [self::GRID_NAME .'[_filter][name][value]' => $name]
        );

        $result = $this->getJsonResponseContent($response, Response::HTTP_OK);
        $result = reset($result['data']);

        $resultData = $result;
        $crawler     = $this->client->request(
            'GET',
            $this->getUrl('marello_customer_company_update', ['id' => $result['id']])
        );
        $result      = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, Response::HTTP_OK);

        /** @var Form $form */
        $form                                   = $crawler->selectButton('Save and Close')->form();
        $name                                   = 'name' . $this->generateRandomString();
        $form['marello_customer_company[name]'] = $name;

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, Response::HTTP_OK);
        $this->assertContains("Company has been saved", $crawler->html());

        $resultData['name'] = $name;

        return $resultData;
    }

    /**
     * @param array $resultData
     *
     * @depends testCompanyUpdate
     */
    public function testCompanyView($resultData)
    {
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('marello_customer_company_view', ['id' => $resultData['id']])
        );

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, Response::HTTP_OK);
        $this->assertContains("{$resultData['name']}", $crawler->html());
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
            'region_text' => $address->getRegionText(),
            'postalCode' => $address->getPostalCode(),
            'phone' =>$address->getPhone(),
            'company' => $address->getCompany()
        ];
    }
}
