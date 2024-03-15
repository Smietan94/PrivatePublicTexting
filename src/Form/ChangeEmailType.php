<?php

namespace App\Form;

use App\Validator\UniqueEmail;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;

class ChangeEmailType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('new_email', TextType::class, [
                'attr'        => ['class' => 'form-control mb-3'],
                'label_attr'  => ['class' => 'form-label text-light'],
                'constraints' => [
                    new Email(),
                    new UniqueEmail()
                ]
            ])
            ->add('update_email', SubmitType::class, ['attr' => [
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
