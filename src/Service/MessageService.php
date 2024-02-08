<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Conversation;
use App\Entity\Message;
use App\Entity\User;
use App\Enum\ConversationType;
use App\Enum\NotificationType;
use App\Form\CreateGroupConversationType;
use App\Form\MessageType;
use App\Repository\ConversationRepository;
use App\Repository\MessageRepository;
use App\Repository\UserRepository;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class MessageService
{
    public function __construct(
        private FormFactoryInterface     $formFactory,
        private MessageAttachmentService $messageAttachmentService,
        private NotificationService      $notificationService,
        private MessageRepository        $messageRepository,
        private ConversationRepository   $conversationRepository,
        private UserRepository           $userRepository,
        private HubInterface             $hub,
        private ValidatorInterface       $validator
    ) {
    }

    /**
     * processMessage
     *
     * @param  ?Conversation $conversation
     * @param  Request       $request
     * @param  string        $messengerTopic
     * @return array
     */
    public function processMessage(?Conversation $conversation = null, Request $request, string $messengerTopic): array
    {
        $form      = $this->formFactory->create(MessageType::class);
        $emptyForm = clone $form;

        $result             = [];
        $result['form']     = $form;
        $result['success']  = null;
        $result['messages'] = [];

        $form->handleRequest($request);

        // checking if use have any conversations
        if (!$conversation) {
            $result['form']     = $form;
            $result['success']  = false;
            $result['messages'] = ['No conversation'];
            return $result;
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $this->processSuccedData($form->getData(), $conversation);

            $this->messageMercureUpdater(
                $messengerTopic,
                $conversation
            );

            //TODO send message preview through notifications channel
            $this->notificationService->messagePreviewMercureUpdater(
                $conversation,
                $data['message'],
                $data['senderId']
            );

            $result['success'] = true;
            $result['form']    = $emptyForm;

        } else if ($form->isSubmitted() && !$form->isValid()) {
            $result = $this->messageFailure($form, $result);
        }

        return $result;
    }

    /**
     * processGroupCreation
     *
     * @param  Request $request
     * @param  User    $currentUser
     * @return array
     */
    public function processGroupCreation(Request $request, User $currentUser): array
    {
        $createGroupForm = $this->formFactory->create(CreateGroupConversationType::class);

        $result             = [];
        $result['form']     = $createGroupForm;
        $result['success']  = null;
        $result['messages'] = [];

        $createGroupForm->handleRequest($request);

        if ($createGroupForm->isSubmitted() && $createGroupForm->isValid()) {
            $data = $createGroupForm->getData();

            // creating new conversation group
            $conversation = $this->conversationRepository->storeConversation(
                $currentUser, 
                $data['friends']->toArray(),
                ConversationType::GROUP->toInt(),
                $data['conversationName'],
            );

            $this->processSuccedData($data, $conversation);

            $this->notificationService->processNewConversationGroupNotification(
                $currentUser,
                $conversation
            );

            $this->notificationService->processFirstGroupMessagePreview($conversation);

            $result['conversationId'] = $conversation->getId();
            $result['success']        = true;

        } else if ($createGroupForm->isSubmitted() && !$createGroupForm->isValid()) {
            $result = $this->messageFailure($createGroupForm, $result);
        }

        return $result;
    }

    /**
     * processSuccedData
     *
     * @param  array        $data
     * @param  Conversation $conversation
     * @return array
     */
    public function processSuccedData(array $data, Conversation $conversation): array
    {
        $haveAttachments = false;

        if (!empty($data['attachment'])) {
            $haveAttachments         = true;
            $data['attachmentPaths'] = $this->messageAttachmentService->processAttachmentUpload(
                $data['attachment'],
                $data['senderId'],
                $conversation->getId()
            );
        }

        // saving message in db
        $message = $this->messageRepository->storeMessage(
            $conversation,
            $data['senderId'],
            $data['message'],
            $haveAttachments
        );

        if ($haveAttachments) {
            $data['attachmentsIds'] = $this->processAttachments(
                $data['attachment'],
                $data['attachmentPaths'],
                $message
            );
        }

        if ($conversation !== null) {
            $this->conversationRepository->updateLastMessage(
                $conversation->getId(),
                $message
            );
        }

        return $data;
    }

    /**
     * messageFailure
     *
     * @param  FormInterface $form
     * @param  array         $result
     * @return array
     */
    public function messageFailure(FormInterface $form, array $result): array
    {
        $result['messages'] = [];

        foreach($this->validator->validate($form) as $error) {
            array_push($result['messages'], $error->getMessage());
        }

        $result['success'] = false;
        $result['form']    = $form;

        return $result;
    }

    /**
     * mercureUpdater
     *
     * @param  string       $topic
     * @param  Conversation $conversationId
     * @return void
     */
    public function messageMercureUpdater(string $topic, Conversation $conversation): void
    {
        $update = new Update(
            sprintf("%s%d", $topic, $conversation->getId()),
            json_encode([
                'messageId' => $conversation->getLastMessage()->getId(),
            ]),
            true
        );

        // publishing mercure update
        $this->hub->publish($update);
    }

    /**
     * processAttachments
     *
     * @param  UploadedFile[] $dataAttachments
     * @param  string[]       $dataAttachmentPaths
     * @param  Message        $message
     * @return int[]
     */
    public function processAttachments(array $dataAttachments, array $dataAttachmentPaths, Message $message): array
    {
        $attachments = $this->messageAttachmentService->processAttachmentsDataStore(
            $dataAttachments,
            $dataAttachmentPaths,
            $message
        );

        return array_map(
            fn($attachment) => $attachment->getId(),
            $attachments
        );
    }
}