<?php

declare(strict_types=1);

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MessageType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('message', TextareaType::class, ['attr' => [
                'rows'             => '2',
                'class'            => 'form-control messenger-input',
                'aria-describedby' => 'button-addon2',
                'placeholder'      => 'Write a message',
                'autocomplete'     => 'off'
            ]])
            ->add('senderId', IntegerType::class, ['attr' => [
                'hidden' => true
            ]])
            ->add('conversationId', IntegerType::class, ['attr' => [
                'hidden' => true
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
