<?php

namespace Marello\Bundle\PurchaseOrderBundle\Tests\Functional\Cron;

use Oro\Bundle\CronBundle\Entity\Repository\ScheduleRepository;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Bundle\FrameworkBundle\Console\Application;

use Oro\Bundle\CronBundle\Entity\Schedule;
use Oro\Bundle\NotificationBundle\Async\Topics;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;

use Marello\Bundle\ProductBundle\Entity\Product;
use Marello\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Marello\Bundle\PurchaseOrderBundle\Cron\PurchaseOrderAdviceCommand;
use Marello\Bundle\InventoryBundle\Tests\Functional\DataFixtures\LoadInventoryData;

class PurchaseOrderAdviceCronTest extends WebTestCase
{
    use MessageQueueExtension;

    /**
     * @var Application
     */
    protected $application;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient();

        $this->application = new Application($this->client->getKernel());
        $this->application->setAutoExit(false);
        $this->application->add(new PurchaseOrderAdviceCommand());
    }

    /**
     * {@inheritdoc}
     */
    public function testAdviceCommandWillNotRunBecauseNoAdvisedItemsFound()
    {
        $command = $this->application->find(PurchaseOrderAdviceCommand::COMMAND_NAME);
        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command]);

        self::assertContains(
            'There are no advised items for PO notification. The command will not run.',
            $commandTester->getDisplay()
        );
        self::assertEquals(PurchaseOrderAdviceCommand::EXIT_CODE, $commandTester->getStatusCode());
    }

    /**
     * {@inheritdoc}
     */
    public function testAdviceCommandWillNotRunBecauseFeatureIsNotEnabled()
    {
        /** @var ConfigManager $configManager */
        $configManager = self::getContainer()->get('oro_config.manager');
        $configManager->set('marello_purchaseorder.purchaseorder_notification', false);

        $command = $this->application->find(PurchaseOrderAdviceCommand::COMMAND_NAME);
        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command]);

        self::assertContains(
            'The PO notification feature is disabled. The command will not run.',
            $commandTester->getDisplay()
        );
        self::assertEquals(PurchaseOrderAdviceCommand::EXIT_CODE, $commandTester->getStatusCode());
    }

    /**
     * {@inheritdoc}
     */
    public function testAdviceCommandWillSendNotification()
    {
        // setup inventory so po candidates are available
        $this->loadFixtures(
            [
                LoadInventoryData::class
            ]
        );
        /** @var ProductRepository $productRepository */
        $productRepository = self::getContainer()
            ->get('doctrine')
            ->getRepository(Product::class);

        $results = $productRepository->getPurchaseOrderItemsCandidates();
        static::assertCount(1, $results);

        /** @var ConfigManager $configManager */
        $configManager = self::getContainer()->get('oro_config.manager');
        // enabled po notification setting again :')
        $configManager->set('marello_purchaseorder.purchaseorder_notification', true);

        $command = $this->application->find(PurchaseOrderAdviceCommand::COMMAND_NAME);
        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command]);

        self::assertEmpty($commandTester->getDisplay());
        self::assertEquals(PurchaseOrderAdviceCommand::EXIT_CODE, $commandTester->getStatusCode());

        self::assertMessageSent(Topics::SEND_NOTIFICATION_EMAIL);
        $message = self::getSentMessage(Topics::SEND_NOTIFICATION_EMAIL);
        self::assertNotContains('{{ entity', $message['subject']);
        self::assertNotContains('{{ entity', $message['body']);
        self::assertEquals('text/html', $message['contentType']);
        self::assertEquals('Purchase Order advise notification', $message['subject']);
    }

    /**
     * {@inheritdoc}
     */
    public function testAdviceCommandIsRegisteredCorrectly()
    {
        /** @var ScheduleRepository $scheduleRepository */
        $scheduleRepository = self::getContainer()
            ->get('doctrine')
            ->getRepository(Schedule::class);
        $crons = $scheduleRepository->findBy(['command' => PurchaseOrderAdviceCommand::COMMAND_NAME]);
        self::assertCount(1, $crons);
    }
}
