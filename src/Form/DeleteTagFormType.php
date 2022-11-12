<?php

declare(strict_types=1);

namespace App\Form;

use App\Form\Model\DeleteTagModel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DeleteTagFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('replacementTag', TextType::class, [
                'required' => false,
                'label' => 'Replacement tag (optional)',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(['data_class' => DeleteTagModel::class]);
    }
}
