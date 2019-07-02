<?php

namespace Marello\Bundle\InventoryBundle\Form\Type;

use Marello\Bundle\InventoryBundle\Entity\InventoryItem;
use Oro\Bundle\EntityExtendBundle\Form\Type\EnumChoiceType;
use Oro\Bundle\FormBundle\Form\Type\OroDateTimeType;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\Valid;

class InventoryItemType extends AbstractType
{
    const BLOCK_PREFIX = 'marello_inventory_item';

    /**
     * @var EventSubscriberInterface
     */
    protected $subscriber;

    /**
     * @param EventSubscriberInterface|null $subscriber
     */
    public function __construct(EventSubscriberInterface $subscriber = null)
    {
        $this->subscriber = $subscriber;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'backorderAllowed',
                CheckboxType::class,
                [
                    'required' => false,
                    'label' => 'marello.inventory.inventoryitem.backorder_allowed.label'
                ]
            )
            ->add(
                'maxQtyToBackorder',
                IntegerType::class,
                [
                    'required' => false,
                    'label' => 'marello.inventory.inventoryitem.max_qty_to_backorder.label',
                    'tooltip'  => 'marello.inventory.form.tooltip.max_qty_to_backorder'
                ]
            )
            ->add(
                'backOrdersDatetime',
                OroDateTimeType::class,
                [
                    'required' => false,
                    'label' => 'marello.inventory.inventoryitem.back_orders_datetime.label',
                    'tooltip'  => 'marello.inventory.form.tooltip.backorder_datetime'
                ]
            )
            ->add(
                'canPreorder',
                CheckboxType::class,
                [
                    'required' => false,
                    'label' => 'marello.inventory.inventoryitem.can_preorder.label'
                ]
            )
            ->add(
                'maxQtyToPreorder',
                IntegerType::class,
                [
                    'required' => false,
                    'label' => 'marello.inventory.inventoryitem.max_qty_to_preorder.label',
                    'tooltip'  => 'marello.inventory.form.tooltip.max_qty_to_preorder'
                ]
            )
            ->add(
                'preOrdersDatetime',
                OroDateTimeType::class,
                [
                    'required' => false,
                    'label' => 'marello.inventory.inventoryitem.pre_orders_datetime.label',
                    'tooltip'  => 'marello.inventory.form.tooltip.preorder_datetime'
                ]
            )
            ->add(
                'replenishment',
                EnumChoiceType::class,
                [
                    'enum_code' => 'marello_inv_reple',
                    'required'  => true,
                    'label'     => 'marello.inventory.inventoryitem.replenishment.label',
                ]
            )
            ->add(
                'desiredInventory',
                NumberType::class,
                [
                    'constraints' => new GreaterThanOrEqual(0)
                ]
            )
            ->add(
                'purchaseInventory',
                NumberType::class,
                [
                    'constraints' => new GreaterThanOrEqual(0)
                ]
            )
            ->add(
                'inventoryLevels',
                InventoryLevelCollectionType::class
            );
        if ($this->subscriber !== null) {
            $builder->addEventSubscriber($this->subscriber);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => InventoryItem::class,
            'constraints' => [
                new Valid()
            ]
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return self::BLOCK_PREFIX;
    }
}
