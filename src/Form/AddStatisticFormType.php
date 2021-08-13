<?php

declare(strict_types=1);

namespace App\Form;

use App\Form\Model\StatisticModel;
use App\Util\TimeType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AddStatisticFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', TextType::class, [
                'attr' => [
                    'placeholder' => 'name'
                ]
            ])
            ->add('description', TextType::class, [
                'required' => false,
                'attr' => [
                    'placeholder' => 'description'
                ]
            ])
            ->add('timeType', ChoiceType::class, [
                'choices' => [
                    TimeType::INSTANT => 'instant',
                    TimeType::INTERVAL => 'interval'
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(['data_class' => StatisticModel::class]);
    }
}
