<?php

declare(strict_types=1);

namespace App\Form;

use App\Form\DataTransformer\TextTimeIntervalSecondsTransformer;
use App\Form\Model\EditTaskPartialModel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EditTaskPartialFormType extends AbstractType
{
    public function __construct(private TextTimeIntervalSecondsTransformer $textDateIntervalTransformer)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class)
            ->add('description', TextareaType::class)
            ->add('priority', IntegerType::class)
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
            ->add('timeEstimate', TextType::class, [
                'attr' => [
                    'placeholder' => '2h5m25s',
                ],
                'invalid_message' => 'This value is invalid. It must be of the form %hours%h%minutes%m%seconds%s or any combination',
                'invalid_message_parameters' => [
                    '%hours%' => 2,
                    '%minutes%' => 35,
                    '%seconds%' => 25,
                ],
                'required' => false,
            ])
            ->add('active', CheckboxType::class, [
                'required' => false,
                'label' => 'Active',
            ])
            ->add('tags', TextType::class, [
                'label' => 'Tags',
                'required' => false,
            ])
        ;

        $builder->get('timeEstimate')->addViewTransformer($this->textDateIntervalTransformer);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => EditTaskPartialModel::class]);

        $resolver->setRequired('timezone');
    }
}
