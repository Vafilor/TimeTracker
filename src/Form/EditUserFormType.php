<?php

declare(strict_types=1);

namespace App\Form;

use App\Form\Model\EditUserModel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TimezoneType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EditUserFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('timezone', TimezoneType::class)
            ->add('dateFormat', TextType::class)
            ->add('dateTimeFormat', TextType::class)
            ->add('todayDateTimeFormat', TextType::class)
            ->add('durationFormat', TextType::class)
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(['data_class' => EditUserModel::class]);
    }
}
