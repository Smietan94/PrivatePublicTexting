<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Constants\Constant;
use App\Entity\Conversation;
use App\Entity\User;
use App\Enum\NotificationType;
use App\Form\AddUsersToConversationType;
use App\Form\ChangeConversationNameType;
use App\Form\CreateGroupConversationType;
use App\Form\RemoveConversationMemberType;
use App\Form\RemoveConversationType;
use App\Form\SearchFormType;
use App\Repository\MessageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Pagerfanta\Adapter\ArrayAdapter;
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
     * @return Pagerfanta
     */
    public function getMsgPager(int $page, Conversation $conversation): Pagerfanta
    {
        $adapter = new ArrayAdapter($conversation->getMessages()->toArray());

        return Pagerfanta::createForCurrentPageWithMaxPerPage(
            $adapter,
            $page,
            Constant::MAX_MESSAGES_PER_PAGE
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
     * @param  NotificationType $type
     * @param  Conversation     $conversation
     * @param  User             $memberToRm
     * @param  ?User            $currentUser
     * @return bool
     */
    public function removeMember(NotificationType $type, Conversation $conversation, User $memberToRm, ?User $currentUser = null): bool
    {
        if ($this->checkIfUserIsMemberOfConversation($conversation, $memberToRm)) {
            $this->notificationService->processConversationMemberRemove(
                $conversation,
                $memberToRm->getId()
            );

            match ($type) {
                NotificationType::REMOVED_FROM_CONVERSATION => $this->notificationService->processConversationMemberRemoveNotification(
                    $currentUser, // if currentUser is null that means
                    $memberToRm,
                    $conversation
                ),
                NotificationType::LEFT_THE_CONVERSATION     => $this->notificationService->processConversationLeftNotification(
                    $memberToRm,
                    $conversation
                )
            };

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
     * @param  User         $currentUser
     * @return bool
     */
    public function changeConversationName(Conversation $conversation, string $conversationName, User $currentUser): bool
    {
        if ($this->checkIfUserIsMemberOfConversation($conversation, $currentUser)) {
            $conversationOldName = $conversation->getName();

            $conversation->setName($conversationName);
            $this->entityManager->flush();

            $this->notificationService->processNameChange($conversation);
            $this->notificationService->processNameChangeNotification(
                $currentUser,
                $conversation,
                $conversationOldName
            );

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
     * createRemoveConversationForm
     *
     * @return FormInterface
     */
    public function createRemoveConversationForm(): FormInterface
    {
        return $this->formFactory->create(RemoveConversationType::class);
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