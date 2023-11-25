<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Conversation;
use App\Entity\User;
use App\Form\AddUsersToConversationType;
use App\Form\ChangeConversationNameType;
use App\Form\MessageType;
use App\Form\RemoveConversationMemberType;
use App\Form\SearchFormType;
use App\Repository\MessageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

class ChatService
{
    public function __construct(
        private MessageRepository $messageRepository,
        private FormFactoryInterface $formFactory,
        private HubInterface $hub,
        private EntityManagerInterface $entityManager
    ) {
    }

    public function getMsgPager(Request $request, Conversation $conversation, int $conversationType): Pagerfanta
    {
        // gets query which prepering all messages from conversation
        $queryBuilder = $this->messageRepository->getMessageQuery(
            $conversation,
            $conversationType
        );

        $adapter = new QueryAdapter($queryBuilder);

        return Pagerfanta::createForCurrentPageWithMaxPerPage(
            $adapter,
            (int) $request->query->get('page', 1),
            10
        );
    }

    public function getRemoveConversationMemberForms(array $conversationMembers, User $currentUser): array
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

    public function getChangeConversationNameForm(): FormInterface
    {
        return $this->formFactory->create(ChangeConversationNameType::class);
    }

    public function processMessage(?Conversation $conversation = null, Request $request, string $topic): FormInterface
    {
        $form      = $this->formFactory->create(MessageType::class);
        $emptyForm = $this->formFactory->create(MessageType::class);

        $form->handleRequest($request);

        // checking if use have any conversations
        if (!$conversation) {
            return $form;
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $data   = $form->getData();

            // creating mercure update
            // TODO make topic env var
            $update = new Update(
                sprintf("%s%d", $topic, $conversation->getId()),
                json_encode([
                    'message' => $data,
                ]),
                true
            );

            // publishing mercure update
            $this->hub->publish($update);

            // saving message in db
            $this->messageRepository->storeMessage(
                $conversation,
                $data['senderId'],
                $data['message']
            );

            return $emptyForm;
        }

        return $form;
    }

    public function removeMember(Conversation $conversation, User $memberToRm ): bool
    {
        if ($this->checkIfUserIsMemberOfConversation($conversation, $memberToRm)) {
            $conversation->removeConversationMember($memberToRm);
            $this->entityManager->flush();

            return true;
        }

        return false;
    }

    public function changeConversationName(Conversation $conversation, string $conversationName, User $user): bool
    {
        if ($this->checkIfUserIsMemberOfConversation($conversation, $user)) {
            $conversation->setName($conversationName);
            $this->entityManager->flush();

            return true;
        }

        return false;
    }

    public function createAddUsersForm(int $conversationId, int $userId): FormInterface
    {
        return $this->formFactory->create(AddUsersToConversationType::class, [
            'conversationId' => $conversationId,
            'currentUserId'  => $userId,
        ]);
    }

    public function createSearchForm(): FormInterface
    {
        return $this->formFactory->create(SearchFormType::class);
    }

    public function checkIfUserIsMemberOfConversation(Conversation $conversation, User $user): bool
    {
        return in_array($conversation, $user->getConversations()->toArray());
    }
}