<?php

declare(strict_types=1);

namespace App\Form;

use App\Form\Model\AddStatisticValueModel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AddStatisticValueFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('statisticName', TextType::class)
            ->add('value', NumberType::class, [
                'attr' => [
                    'placeholder' => 'value',
                ],
            ])
            ->add('day', DateType::class, [
                'required' => false,
                'widget' => 'single_text',
                'view_timezone' => $options['timezone'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(['data_class' => AddStatisticValueModel::class]);

        $resolver->setRequired(['timezone']);
    }
}
