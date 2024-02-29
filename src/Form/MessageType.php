<?php

declare(strict_types=1);

namespace App\Form;

use App\Validator\AttachmentValidator\AttachmentType;
use App\Validator\AttachmentValidator\MaxFileUploads;
use App\Validator\AttachmentValidator\UploadSize;
use App\Validator\AttachmentValidator\FileName;
use App\Validator\FileExtension;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * MessageType
 */
class MessageType extends AbstractType
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
            ->add('message', TextareaType::class, ['attr' => [
                'rows'             => '2',
                'class'            => 'form-control messenger-input',
                'aria-describedby' => 'send-msg-button',
                'placeholder'      => 'Write a message',
                'autocomplete'     => 'off'
            ]])
            ->add('attachment', FileType::class, [
                'attr' => [
                    'class' => 'form-control messenger-input',
                    'style' => 'visibility:hidden; position:absolute'
                ],
                'error_bubbling' => true,
                'required'       => false,
                'multiple'       => true,
                'constraints'    => [
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
            ->add('conversationId', IntegerType::class, ['attr' => [
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
