<?php

declare(strict_types=1);

namespace App\Form;

use App\Form\Model\EditTimestampModel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EditTimestampFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('createdAt', DateTimeType::class, [
                'widget' => 'single_text',
                'with_seconds' => true,
                'view_timezone' => $options['timezone']
            ])
            ->add('description', TextAreaType::class)
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
                                   'data_class' => EditTimestampModel::class
                               ]);

        $resolver->setRequired(['timezone']);
    }
}
