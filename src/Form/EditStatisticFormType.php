<?php

declare(strict_types=1);

namespace App\Form;

use App\Form\Model\EditStatisticModel;
use App\Util\TimeType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\ColorType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EditStatisticFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', TextType::class, [
                'required' => false,
            ])
            ->add('color', ColorType::class)
            ->add('icon', TextType::class, [
                'help' => "Choose a free icon from <a href=\"https://fontawesome.com/\" target='_blank'>Font Awesome</a>",
                'help_html' => true,
                'required' => false,
            ])
            ->add('description', TextareaType::class, [
                'required' => false,
            ])
            ->add('unit', TextType::class, [
                'required' => false,
            ])
            ->add('timeType', ChoiceType::class, [
                'choices' => [
                    TimeType::INSTANT => 'instant',
                    TimeType::INTERVAL => 'interval',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(['data_class' => EditStatisticModel::class]);
    }
}
