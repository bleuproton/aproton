<?php

namespace Marello\Bundle\PaymentTermBundle\Tests\Functional\Controller;

use Marello\Bundle\PaymentTermBundle\Tests\Functional\DataFixtures\LoadPaymentTermsData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class SystemConfigurationControllerTest extends WebTestCase
{
    const BLOCK_PREFIX = 'payment_config';
    const SAVE_MESSAGE = 'Configuration saved';

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader()
        );

        $this->loadFixtures([
            LoadPaymentTermsData::class,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function testSystemConfiguration()
    {
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_config_configuration_system', [
                'activeGroup' => 'marello',
                'activeSubGroup' => 'payment_config',
            ])
        );

        static::assertResponseStatusCodeEquals($this->client->getResponse(), Response::HTTP_OK);
        static::assertCount(
            1,
            $crawler->filter('select[name="payment_config[marello_payment_term___default_payment_term][value]"]')
        );

        $form = $crawler->selectButton('Save settings')->form();
        $selectField = $form['payment_config[marello_payment_term___default_payment_term][value]'];

        static::assertEquals(
            $this->getReference(LoadPaymentTermsData::PAYMENT_TERM_1_REF)->getId(),
            $selectField->getValue()
        );
    }

    public function testSystemConfigurationUpdate()
    {
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_config_configuration_system', [
                'activeGroup' => 'marello',
                'activeSubGroup' => 'payment_config',
            ])
        );

        $token = $this->getContainer()
            ->get('security.csrf.token_manager')
            ->getToken(self::BLOCK_PREFIX)
            ->getValue()
        ;

        $formData = [
            'input_action' => '',
            self::BLOCK_PREFIX => [
                'marello_payment_term___default_payment_term' => [
                    'value' => $this->getReference(LoadPaymentTermsData::PAYMENT_TERM_2_REF)->getId(),
                ],
                '_token' => $token,
            ],
        ];

        $form = $crawler->selectButton('Save settings')->form();

        $this->client->followRedirects(true);
        $crawler = $this->client->request($form->getMethod(), $form->getUri(), $formData);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, Response::HTTP_OK);

        $form = $crawler->selectButton('Save settings')->form();
        $selectField = $form['payment_config[marello_payment_term___default_payment_term][value]'];

        static::assertEquals(
            $this->getReference(LoadPaymentTermsData::PAYMENT_TERM_2_REF)->getId(),
            $selectField->getValue()
        );
    }
}
