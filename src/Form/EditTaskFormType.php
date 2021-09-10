<?php

declare(strict_types=1);

namespace App\Form;

use App\Form\Model\AddTagModel;
use App\Form\Model\EditTaskModel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EditTaskFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', TextType::class)
            ->add('description', TextareaType::class, [
                'required' => false,
            ])
            ->add('completedAt', DateTimeType::class, [
                'required' => false,
                'widget' => 'single_text',
                'view_timezone' => $options['timezone'],
            ])
            ->add('dueAt', DateTimeType::class, [
                'required' => false,
                'widget' => 'single_text',
                'view_timezone' => $options['timezone'],
            ])
            ->add('parentTask', TextType::class, [
                'required' => false
            ])
            ->add('template', CheckboxType::class, [
                'required' => false,
                'label' => 'Is Template'
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(['data_class' => EditTaskModel::class]);

        $resolver->setRequired('timezone');
    }
}
