<?php

namespace MarelloEnterprise\Bundle\InventoryBundle\Form\Extension;

use Marello\Bundle\InventoryBundle\Form\Type\WarehouseType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;

class WarehouseExtension extends AbstractTypeExtension
{
    /**
     * {@inheritdoc}
     */
    public static function getExtendedTypes()
    {
        return [WarehouseType::class];
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder
            ->add('default', CheckboxType::class, [
                'required' => false,
                'tooltip'  => 'marelloenterprise.inventory.warehouse.delete',
            ])
            ->add('warehouseType', EntityType::class, [
                'label'    => 'marello.inventory.warehouse.warehouse_type.label',
                'class'    => 'MarelloInventoryBundle:WarehouseType',
                'required' => true,
            ]);
    }
}
