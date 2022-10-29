<?php

declare(strict_types=1);

namespace App\Form;

use App\Form\Model\FilterTimeEntryModel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FilterTimeEntryFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('start', DateTimeType::class, [
                'widget' => 'single_text',
                'view_timezone' => $options['timezone'],
                'required' => false,
                'invalid_message' => 'Not valid. Fill out both date and time.',
            ])
            ->add('end', DateTimeType::class, [
                'widget' => 'single_text',
                'view_timezone' => $options['timezone'],
                'required' => false,
                'invalid_message' => 'Not valid. Fill out both date and time.',
            ])
            ->add('tags', TextType::class, [
                'required' => false,
            ])
            ->add('taskId', TextType::class, [
                'required' => false,
                'label' => 'Task',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(['data_class' => FilterTimeEntryModel::class]);
        $resolver->setRequired(['timezone']);
    }
}
