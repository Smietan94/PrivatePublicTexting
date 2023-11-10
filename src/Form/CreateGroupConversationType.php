<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CreateGroupConversationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('friend', UserAutocompleteField::class)
            ->add('conversationName', TextType::class, [
                'required' => false,
                'attr'     => [
                    'class'       => 'form-control mt-2',
                    'placeholder' => 'Conversation name'
                ]
            ])
            ->add('message', TextareaType::class, [
                'attr' => [
                    'rows'             => '2',
                    'class'            => 'form-control messenger-input',
                    'aria-describedby' => 'button-addon2',
                    'placeholder'      => 'To start conversation send the message',
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Configure your form options here
        ]);
    }
}
