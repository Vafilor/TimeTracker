<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Task;
use App\Entity\User;
use App\Form\Model\TimeEntryModel;
use App\Repository\TaskRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TimeEntryFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('startedAt', DateTimeType::class, [
                'widget' => 'single_text',
                'with_seconds' => true,
                'view_timezone' => $options['timezone'],
                'required' => false
            ])
            ->add('endedAt', DateTimeType::class, [
                'widget' => 'single_text',
                'with_seconds' => true,
                'view_timezone' => $options['timezone'],
                'required' => false,
            ])
            ->add('description', TextareaType::class, [
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
                                   'data_class' => TimeEntryModel::class
                               ]);

        $resolver->setRequired(['timezone']);
    }
}

