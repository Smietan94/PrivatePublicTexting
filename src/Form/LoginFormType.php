<?php

declare(strict_types=1);

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;

class LoginFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $default_styling = [
            'attr'       => ['class' => 'form-control form-control-lg'],
            'label_attr' => ['class' => 'form-label text-light']
        ];

        $builder
            ->add('email', EmailType::class, $default_styling + [
                'constraints' => [new Email()]
            ])
            ->add('password', PasswordType::class, $default_styling)
            ->add('submit', SubmitType::class, [
                'attr'  => ['class' => 'btn btn-outline-light btn-lg px-5'],
                'label' => 'Login'
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Configure your form options here
            'data_class' => null
        ]);
    }
}
