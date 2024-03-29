<?php

declare(strict_types=1);

namespace App\Form;

use App\Validator\Password;
use App\Validator\UniqueEmail;
use App\Validator\UniqueUserName;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;

/**
 * RegisterFormType
 */
class RegisterFormType extends AbstractType
{
    /**
     * buildForm
     *
     * @param  FormBuilderInterface $builder
     * @param  array                $options
     * @return void
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $default_styling = [
            'attr'       => ['class' => 'form-control form-control-lg'],
            'label_attr' => ['class' => 'form-label text-light']
        ];

        $builder
            ->add('name', TextType::class, $default_styling + [
                'constraints' => [
                    new Length(['max' => 50])
                ]
            ])
            ->add('user_name', TextType::class, $default_styling + [
                'constraints' => [
                    new Length(['max' => 50]),
                    new UniqueUserName()
                ]
            ])
            ->add('email', EmailType::class, $default_styling + [
                'constraints' => [
                    new Email(),
                    new UniqueEmail()
                ]
            ])
            ->add('password', PasswordType::class, $default_styling + [
                'constraints' => [
                    new Password(),
                    new Length([
                        'min' => 8,
                        'minMessage' => 'Your password should be at least {{ limit }} characters.'
                    ])
                ]
            ])
            ->add('confirm_password', PasswordType::class, $default_styling + [
                'constraints' => [
                    new Password(),
                    new Length([
                        'min' => 8,
                        'minMessage' => 'Your password should be at least {{ limit }} characters.'
                    ])
                ]
            ])
            ->add('submit', SubmitType::class, [
                'attr'  => [
                    'class' => 'btn btn-outline-light btn-lg px-5'
                ],
                'label' => 'Register',
            ])
        ;
    }

    /**
     * configureOptions
     *
     * @param  OptionsResolver $resolver
     * @return void
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Configure your form options here
        ]);
    }
}
