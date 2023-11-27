<?php

declare(strict_types=1);

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * ChangeConversationNameType
 */
class ChangeConversationNameType extends AbstractType
{
    /**
     * buildForm
     *
     * @param  FormBuilderInterface $builder
     * @param  array $options
     * @return void
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('conversationName', TextType::class, ['attr' => [
                'class' => 'form-control'
            ]])
            ->add('conversationId', HiddenType::class)
            ->add('changeName', SubmitType::class, ['attr' => [
                'class' => 'btn btn-primary'
            ]])
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
