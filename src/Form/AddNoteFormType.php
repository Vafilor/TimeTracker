<?php

declare(strict_types=1);

namespace App\Form;

use App\Form\Model\AddNoteModel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AddNoteFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('title', TextType::class, [
                'required' => true,
                'attr' => [
                    'placeholder' => 'title',
                ],
            ])
            ->add('content', TextType::class, [
                'required' => false,
                'attr' => [
                    'placeholder' => 'content',
                ],
            ])
            ->add('forDate', DateType::class, [
                'widget' => 'single_text',
                'view_timezone' => $options['timezone'],
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(['data_class' => AddNoteModel::class]);
        $resolver->setRequired(['timezone']);
    }
}
