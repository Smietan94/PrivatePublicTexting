<?php

namespace App\Form;

use App\Validator\Password;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;

class ChangePasswordType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $default_styling = [
            'attr'        => ['class' => 'form-control'],
            'label_attr'  => ['class' => 'form-label text-light'],
            'constraints' => [
                new Password(),
                new Length([
                    'min' => 8,
                    'minMessage' => 'Your password should be at least {{ limit }} characters.'
                ])
            ]
        ];

        $builder
            ->add('password', PasswordType::class, $default_styling)
            ->add('confirm_password', PasswordType::class, $default_styling)
            ->add('update_password', SubmitType::class, ['attr' => [
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
