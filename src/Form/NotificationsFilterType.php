<?php

namespace App\Form;

use App\Enum\NotificationType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class NotificationsFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
        ->add('users', ChoiceType::class, [
            'multiple'     => true,
            // 'choice_label' => 'notificationType',
            // 'choice_value' => 'notificationType',
            'choices'      => array_column(NotificationType::cases(), 'value'),
            'autocomplete' => true,
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
