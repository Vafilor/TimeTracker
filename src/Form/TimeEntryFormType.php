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
                'date_widget' => 'single_text',
                'time_widget' => 'single_text',
                'view_timezone' => $options['timezone']
            ])
            ->add('endedAt', DateTimeType::class, [
                'date_widget' => 'single_text',
                'time_widget' => 'single_text',
                'view_timezone' => $options['timezone'],
                'required' => false,
            ])
            ->add('description', TextareaType::class, [
                'required' => false,
            ])
        ;

        if (array_key_exists('user', $options)) {
            $builder->add('task', EntityType::class, [
                'class' => Task::class,
                'choice_label' => 'name',
                'required' => false,
                'placeholder' => 'Optionally assign to a task',
                'query_builder' => function (TaskRepository $tr) use ($options) {
                    return $tr->findByUserQueryBuilder($options['user'])
                              ->orderBy('task.name', 'ASC')
                    ;
                },
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
                                   'data_class' => TimeEntryModel::class
                               ]);

        $resolver->setDefined(['user']);
        $resolver->setAllowedTypes('user', User::class);
        $resolver->setRequired(['timezone']);
    }
}

