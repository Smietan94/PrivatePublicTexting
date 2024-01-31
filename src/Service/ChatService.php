<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Conversation;
use App\Entity\User;
use App\Form\AddUsersToConversationType;
use App\Form\ChangeConversationNameType;
use App\Form\CreateGroupConversationType;
use App\Form\RemoveConversationMemberType;
use App\Form\SearchFormType;
use App\Repository\MessageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;

class ChatService
{
    public function __construct(
        private MessageRepository      $messageRepository,
        private FormFactoryInterface   $formFactory,
        private EntityManagerInterface $entityManager,
        private NotificationService    $notificationService
    ) {
    }

    /**
     * getMsgPager
     *
     * @param  int          $page
     * @param  Conversation $conversation
     * @param  int          $conversationType
     * @return Pagerfanta
     */
    public function getMsgPager(int $page, Conversation $conversation, int $conversationType): Pagerfanta
    {
        // gets query which prepering all messages from conversation
        $query = $this->messageRepository->getMessageQuery(
            $conversation,
            $conversationType
        );

        $adapter = new QueryAdapter($query);

        return Pagerfanta::createForCurrentPageWithMaxPerPage(
            $adapter,
            $page,
            10
        );
    }

    /**
     * getRemoveConversationMemberForms
     *
     * @param  User[] $conversationMembers
     * @return array
     */
    public function getRemoveConversationMemberForms(array $conversationMembers): array
    {
        $forms = array_map(
            fn() => $this->formFactory->create(RemoveConversationMemberType::class)->createView(),
            $conversationMembers
        );

        $formsFormatted = [];

        foreach ($conversationMembers as $key => $member) {
            $formsFormatted[$member->getId()] = $forms[$key];
        }

        return $formsFormatted;
    }

    /**
     * getChangeConversationNameForm
     *
     * @return FormInterface
     */
    public function getChangeConversationNameForm(): FormInterface
    {
        return $this->formFactory->create(ChangeConversationNameType::class);
    }

    /**
     * removeMember
     *
     * @param  Conversation $conversation
     * @param  User         $memberToRm
     * @return bool
     */
    public function removeMember(Conversation $conversation, User $memberToRm): bool
    {
        if ($this->checkIfUserIsMemberOfConversation($conversation, $memberToRm)) {
            $this->notificationService->processConversationMemberRemove($conversation, $memberToRm->getId());

            $conversation->removeConversationMember($memberToRm);
            $this->entityManager->flush();

            return true;
        }

        return false;
    }

    /**
     * changeConversationName
     *
     * @param  Conversation $conversation
     * @param  string       $conversationName
     * @param  User         $user
     * @return bool
     */
    public function changeConversationName(Conversation $conversation, string $conversationName, User $user): bool
    {
        if ($this->checkIfUserIsMemberOfConversation($conversation, $user)) {
            $conversation->setName($conversationName);
            $this->entityManager->flush();

            $this->notificationService->processNameChange($conversation);

            return true;
        }

        return false;
    }

    /**
     * createAddUsersForm
     *
     * @param  int $conversationId
     * @param  int $userId
     * @return FormInterface
     */
    public function createAddUsersForm(int $conversationId, int $userId): FormInterface
    {
        return $this->formFactory->create(AddUsersToConversationType::class, [
            'conversationId' => $conversationId,
            'currentUserId'  => $userId,
        ]);
    }

    /**
     * createSearchForm
     *
     * @return FormInterface
     */
    public function createSearchForm(): FormInterface
    {
        return $this->formFactory->create(SearchFormType::class);
    }

    /**
     * createGroupCreationForm
     *
     * @return FormInterface
     */
    public function createGroupCreationForm(): FormInterface
    {
        return $this->formFactory->create(CreateGroupConversationType::class);
    }

    /**
     * checkIfUserIsMemberOfConversation
     *
     * @param  Conversation $conversation
     * @param  User         $user
     * @return bool
     */
    public function checkIfUserIsMemberOfConversation(Conversation $conversation, User $user): bool
    {
        return in_array($conversation, $user->getConversations()->toArray());
    }
}