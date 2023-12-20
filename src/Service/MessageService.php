<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Conversation;
use App\Entity\Message;
use App\Form\MessageType;
use App\Repository\ConversationRepository;
use App\Repository\MessageRepository;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class MessageService
{
    public function __construct(
        private FormFactoryInterface $formFactory,
        private MessageAttachmentService $messageAttachmentService,
        private MessageRepository $messageRepository,
        private ConversationRepository $conversationRepository,
        private HubInterface $hub,
        private ValidatorInterface $validator
    ) {
    }

    /**
     * processMessage
     *
     * @param  ?Conversation $conversation
     * @param  Request       $request
     * @param  string        $topic
     * @return array
     */
    public function processMessage(?Conversation $conversation = null, Request $request, string $topic): array
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
            $data = $this->processSuccedData($form, $conversation);

            $this->mercureUpdater($topic, $conversation->getId(), $data);

            $result['success'] = true;
            $result['form']    = $emptyForm;
        }
        else if ($form->isSubmitted() && !$form->isValid()) {
            $result = $this->messageFailure($form, $result);
        }

        return $result;
    }
    
    /**
     * processSuccedData
     *
     * @param  FormInterface $form
     * @param  Conversation  $conversation
     * @return array
     */
    public function processSuccedData(FormInterface $form, Conversation $conversation): array
    {
        $data            = $form->getData();
        $haveAttachments = false;
        // creating mercure update
        // TODO make topic env var
        if (!empty($data['attachment'])) {
            $haveAttachments = true;
            $data['attachmentPaths'] = $this->messageAttachmentService->processAttachmentUpload($data['attachment'], $data['senderId'], $conversation->getId());
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
     * @param  string $topic
     * @param  int    $conversationId
     * @param  array  $data
     * @return void
     */
    public function mercureUpdater(string $topic, int $conversationId, array $data): void
    {
        $update = new Update(
            sprintf("%s%d", $topic, $conversationId),
            json_encode([
                'message' => $data,
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