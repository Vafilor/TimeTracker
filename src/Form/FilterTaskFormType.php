<?php

declare(strict_types=1);

namespace App\Form;

use App\Form\Model\FilterTaskModel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SearchType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FilterTaskFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('showCompleted', CheckboxType::class, [
                'required' => false,
                'false_values' => [null, '0'],
            ])
            ->add('showClosed', CheckboxType::class, [
                'required' => false,
                'false_values' => [null, '0'],
            ])
            ->add('showSubtasks', CheckboxType::class, [
                'required' => false,
                'false_values' => [null, '0'],
            ])
            ->add('onlyShowPastDue', CheckboxType::class, [
                'required' => false,
                'false_values' => [null, '0'],
            ])
            ->add('onlyTemplates', CheckboxType::class, [
                'required' => false,
                'false_values' => [null, '0'],
                'label' => 'Only show templates',
            ])
            ->add('content', SearchType::class, [
                'required' => false,
            ])
            ->add('tags', TextType::class, [
                'required' => false,
            ])
            ->add('parentTask', TextType::class, [
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => FilterTaskModel::class]);
    }
}
