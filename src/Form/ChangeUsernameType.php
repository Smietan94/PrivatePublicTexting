<?php

namespace App\Form;

use App\Validator\UniqueUserName;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ChangeUsernameType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('new_username', TextType::class, [
                'attr'        => ['class' => 'form-control'],
                'label_attr'  => ['class' => 'form-label text-light'],
                'constraints' => [
                    new UniqueUserName()
                ]
            ])
            ->add('update_username', SubmitType::class, ['attr' => [
                'class' => 'btn btn-primary w-100'
            ]])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Configure your form options here
        ]);
    }
}
