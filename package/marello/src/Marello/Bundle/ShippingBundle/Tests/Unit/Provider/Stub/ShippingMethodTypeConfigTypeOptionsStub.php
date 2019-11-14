<?php

namespace Marello\Bundle\ShippingBundle\Tests\Unit\Provider\Stub;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class ShippingMethodTypeConfigTypeOptionsStub extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('price', TextType::class)
            ->add('handling_fee', TextType::class)
            ->add('type', TextType::class);
    }
}
