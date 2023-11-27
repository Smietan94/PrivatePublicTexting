<?php

namespace App\Form;

use App\Repository\ConversationRepository;
use App\Repository\UserRepository;
use App\Service\ChatService;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * AddUsersToConversationType
 */
class AddUsersToConversationType extends AbstractType
{
    public function __construct(
        private Security $security,
        private ConversationRepository $conversationRepository,
        private ChatService $chatService,
        private UserRepository $userRepository
    ) {
    }

    /**
     * buildForm
     *
     * @param  FormBuilderInterface $builder
     * @param  array $options
     * @return void
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $conversationId = $options['data']['conversationId'];
        $userId         = $options['data']['currentUserId'];

        // TODO submit
        $builder
            ->add('conversationId', HiddenType::class, [
                'data' => $options['data']['conversationId']
            ])
            ->add('users', ChoiceType::class, [
                'multiple' => true,
                'choice_label' => 'username',
                'choice_value' => 'id',
                'choices' => $this->userRepository->getNotConversationMemberFriends($userId, $conversationId),
                'autocomplete' => true,
            ])
            ->add('addUsers', SubmitType::class)
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
            'conversationId' => null,
            'currentUserId'  => null
        ]);
    }

    
}
