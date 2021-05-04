<?php

declare(strict_types=1);

namespace App\Form;

use App\Form\Model\TaskListFilterModel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TaskListFilterFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('showCompleted', CheckboxType::class, [
                'required' => false
            ])
            ->add('description', TextType::class, [
                'required' => false
            ])
            ->add('name', TextType::class, [
                'required' => false
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
                                   'data_class' => TaskListFilterModel::class,
                               ]);
    }
}
