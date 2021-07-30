<?php

declare(strict_types=1);

namespace App\Form;

use App\Form\Model\TimestampEditModel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TimestampEditFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('createdAt', DateTimeType::class, [
                'widget' => 'single_text',
                'with_seconds' => true,
                'view_timezone' => $options['timezone']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
                                   'data_class' => TimestampEditModel::class
                               ]);

        $resolver->setRequired(['timezone']);
    }
}
