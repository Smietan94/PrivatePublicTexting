<?php

declare(strict_types=1);

namespace App\Form;

use App\Validator\AttachmentValidator\AttachmentType;
use App\Validator\AttachmentValidator\FileName;
use App\Validator\AttachmentValidator\MaxFileUploads;
use App\Validator\AttachmentValidator\UploadSize;
use App\Validator\FileExtension;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * CreateGroupConversationType
 */
class CreateGroupConversationType extends AbstractType
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
        $builder
            ->add('friends', UserAutocompleteField::class)
            ->add('conversationName', TextType::class, [
                'required' => false,
                'attr'     => [
                    'class'       => 'form-control mt-2',
                    'placeholder' => 'Conversation name'
                ]
            ])
            ->add('message', TextareaType::class, ['attr' => [
                'rows'             => '2',
                'class'            => 'form-control messenger-input',
                'aria-describedby' => 'button-addon2',
                'placeholder'      => 'To start conversation send the message',
            ]])
            ->add('attachment', FileType::class, [
                'attr' => [
                    'class' => 'form-control messenger-input',
                    'style' => 'display:none'
                ],
                'error_bubbling' => true,
                'required' => false,
                'multiple' => true,
                'constraints' => [
                    new FileName(),
                    new FileExtension(),
                    new AttachmentType(),
                    new MaxFileUploads(),
                    new UploadSize(),
                ]
            ])
            ->add('senderId', IntegerType::class, ['attr' => [
                'hidden' => true
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
