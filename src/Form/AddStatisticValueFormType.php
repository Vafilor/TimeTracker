<?php

declare(strict_types=1);

namespace App\Form;

use App\Form\Model\AddStatisticValue;
use App\Form\Model\StatisticEditModel;
use App\Util\TimeType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AddStatisticValueFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('statisticName', TextType::class)
            ->add('value', TextType::class)
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
                                   'data_class' => AddStatisticValue::class
                               ]);
    }
}
