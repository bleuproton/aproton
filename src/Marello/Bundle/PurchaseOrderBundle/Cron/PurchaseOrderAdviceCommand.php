<?php

namespace Marello\Bundle\PurchaseOrderBundle\Cron;

use Marello\Bundle\OrderBundle\Entity\Customer;
use Marello\Bundle\ProductBundle\Entity\Product;
use Marello\Bundle\PurchaseOrderBundle\Entity\PurchaseOrder;
use Marello\Bundle\PurchaseOrderBundle\Entity\PurchaseOrderItem;
use Oro\Bundle\CronBundle\Command\CronCommandInterface;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PurchaseOrderAdviceCommand extends ContainerAwareCommand implements CronCommandInterface
{
    /**
     * Command name
     */
    const COMMAND_NAME = 'marello:cron:po-advice';

    /**
     * @inheritDoc
     */
    public function getDefaultDefinition()
    {
        return '0 13 * * *';
    }

    /**
     * @inheritDoc
     */
    public function isActive()
    {
        $featureChecker = $this->getContainer()->get('oro_featuretoggle.checker.feature_checker');
        $configManager = $this->getContainer()->get('oro_config.manager');

        return $featureChecker->isResourceEnabled(self::COMMAND_NAME, 'cron_jobs') &&
        $configManager->get('marello_purchaseorder.purchaseorder_notification') === true;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::COMMAND_NAME)
            ->setDescription('Sending Purchase Orders advice notification');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $configManager = $this->getContainer()->get('oro_config.manager');
        if ($configManager->get('marello_purchaseorder.purchaseorder_notification') !== true) {
            $output->writeln('The PO notification feature is disabled. The command will not run.');

            return 0;
        }

        $doctrine = $this->getContainer()->get('doctrine');
        $advisedItems = $doctrine
            ->getManagerForClass(Product::class)
            ->getRepository(Product::class)
            ->getPurchaseOrderItemsCandidates();
        if (empty($advisedItems)) {
            $output->writeln('There are no advised items for PO notification. The command will not run.');

            return 0;
        }
        $entity = new PurchaseOrder();
        $organization = $doctrine
            ->getManagerForClass(Organization::class)
            ->getRepository(Organization::class)
            ->getFirst();
        $entity->setOrganization($organization);
        foreach ($advisedItems as $advisedItem) {
            $poItem = new PurchaseOrderItem();
            $poItem
                ->setSupplier($advisedItem['supplier'])
                ->setProductSku($advisedItem['sku'])
                ->setOrderedAmount((double)$advisedItem['orderAmount']);
            $entity->addItem($poItem);
        }
        $recipient = new Customer();
        $recipient->setEmail($configManager->get('marello_purchaseorder.purchaseorder_notification_address'));
        $sendProcessor = $this->getContainer()->get('marello_notification.email.send_processor');
        $sendProcessor->sendNotification(
            'marello_purchase_order_advise',
            [$recipient],
            $entity,
            [],
            false
        );

        return 0;
    }
}
