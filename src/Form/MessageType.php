<?php

declare(strict_types=1);

namespace App\Form;

use App\Validator\AttachmentValidator\AttachmentType;
use App\Validator\AttachmentValidator\MaxFileUploads;
use App\Validator\AttachmentValidator\UploadSize;
use App\Validator\AttachmentValidator\FileName;
use App\Validator\AttachmentValidator\FileExtension;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
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
            ->add('sendMessage', SubmitType::class, [
                'attr' => [
                    'class' => 'btn btn-primary px-4'
                ],
                'label' =>'
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-send" viewBox="0 0 16 16">
                        <path d="M15.854.146a.5.5 0 0 1 .11.54l-5.819 14.547a.75.75 0 0 1-1.329.124l-3.178-4.995L.643 7.184a.75.75 0 0 1 .124-1.33L15.314.037a.5.5 0 0 1 .54.11ZM6.636 10.07l2.761 4.338L14.13 2.576 6.636 10.07Zm6.787-8.201L1.591 6.602l4.339 2.76 7.494-7.493Z"/>
                    </svg>
                ',
                'label_html' => true,
            ])
        ;
    }

//     <button class="btn btn-primary px-4" type="submit" id="send-msg-button">
    // <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-send" viewBox="0 0 16 16">
    //     <path d="M15.854.146a.5.5 0 0 1 .11.54l-5.819 14.547a.75.75 0 0 1-1.329.124l-3.178-4.995L.643 7.184a.75.75 0 0 1 .124-1.33L15.314.037a.5.5 0 0 1 .54.11ZM6.636 10.07l2.761 4.338L14.13 2.576 6.636 10.07Zm6.787-8.201L1.591 6.602l4.339 2.76 7.494-7.493Z"/>
    // </svg>
//     </button>

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
