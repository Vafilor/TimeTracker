<?php

declare(strict_types=1);

namespace App\Form;

use App\Form\Model\StatisticEditModel;
use App\Form\Model\StatisticModel;
use App\Form\Model\TagModel;
use App\Util\TimeType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\ColorType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class StatisticEditFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('description', TextareaType::class, [
                'required' => false
            ])
            ->add('valueType', TextType::class)
            ->add('timeType', ChoiceType::class, [
                'choices' => [
                    TimeType::instant => 'instant',
                    TimeType::interval => 'interval'
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
                                   'data_class' => StatisticEditModel::class
                               ]);
    }
}
