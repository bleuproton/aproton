<?php

namespace Marello\Bundle\Magento2Bundle\ImportExport\Writer;

use Akeneo\Bundle\BatchBundle\Entity\StepExecution;
use Akeneo\Bundle\BatchBundle\Item\ItemWriterInterface;
use Akeneo\Bundle\BatchBundle\Step\StepExecutionAwareInterface;
use Marello\Bundle\Magento2Bundle\DTO\ProductIdentifierDTO;

class InternalMagentoProductWriter implements ItemWriterInterface, StepExecutionAwareInterface
{
    /** @var string */
    public const INTERNAL_MAGENTO_PRODUCT_IDS_CONTEXT = 'internalMagentoProductIDs';

    /** @var StepExecution */
    protected $stepExecution;

    /**
     * @param ProductIdentifierDTO[] $items
     */
    public function write(array $items)
    {
        $existedMagentoProducts = $this->stepExecution
            ->getJobExecution()
            ->getExecutionContext()
            ->get(self::INTERNAL_MAGENTO_PRODUCT_IDS_CONTEXT) ?? [];

        foreach ($items as $item) {
            $existedMagentoProducts[$item->getMarelloProductId()] = $item->getMagentoProductId();
        }

        $this->stepExecution
            ->getJobExecution()
            ->getExecutionContext()
            ->put(self::INTERNAL_MAGENTO_PRODUCT_IDS_CONTEXT, $existedMagentoProducts);
    }

    /**
     * @param StepExecution $stepExecution
     */
    public function setStepExecution(StepExecution $stepExecution)
    {
        $this->stepExecution = $stepExecution;
    }
}
