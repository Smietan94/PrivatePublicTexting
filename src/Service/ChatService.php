<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Conversation;
use App\Entity\Message;
use App\Entity\MessageAttachment;
use App\Entity\User;
use App\Form\AddUsersToConversationType;
use App\Form\ChangeConversationNameType;
use App\Form\MessageType;
use App\Form\RemoveConversationMemberType;
use App\Form\SearchFormType;
use App\Repository\ConversationRepository;
use App\Repository\MessageAttachmentRepository;
use App\Repository\MessageRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use League\Flysystem\FilesystemOperator;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ChatService
{
    public function __construct(
        private MessageRepository $messageRepository,
        private MessageAttachmentRepository $messageAttachmentRepository,
        private ConversationRepository $conversationRepository,
        private FormFactoryInterface $formFactory,
        private HubInterface $hub,
        private EntityManagerInterface $entityManager,
        private ValidatorInterface $validator,
        private FilesystemOperator $defaultStorage,
    ) {
    }

    /**
     * getMsgPager
     *
     * @param  int $page
     * @param  Conversation $conversation
     * @param  int $conversationType
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
     * processMessage
     *
     * @param  ?Conversation $conversation
     * @param  Request $request
     * @param  string $topic
     * @return array
     */
    public function processMessage(
        ?Conversation $conversation = null,
        Request $request, 
        string $topic
    ): array {
        $form      = $this->formFactory->create(MessageType::class);
        $emptyForm = clone $form;

        $result            = [];
        $result['form']    = $form;
        $result['success'] = null;

        $form->handleRequest($request);

        // checking if use have any conversations
        if (!$conversation) {
            $result['form']     = $form;
            $result['success']  = false;
            $result['messages'] = ['No conversation'];
            return $result;
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $data            = $form->getData();
            $haveAttachments = false;
            // creating mercure update
            // TODO make topic env var
            if (!empty($data['attachment'])) {
                $haveAttachments = true;
                $data['attachmentPaths'] = $this->processAttachmentUpload($data['attachment'], $data['senderId']);
            }

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
            $message = $this->messageRepository->storeMessage(
                $conversation,
                $data['senderId'],
                $data['message'],
                $haveAttachments
            );

            if ($haveAttachments) {
                $this->processAttachmentsDataStore(
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

            $result['success'] = true;
            $result['form']    = $emptyForm;
        }
        else if ($form->isSubmitted() && !$form->isValid()) {
            $result['messages'] = [];

            foreach($this->validator->validate($form) as $error) {
                array_push($result['messages'], $error->getMessage());
            }

            $result['success'] = false;
            $result['form']    = $form;
        }

        return $result;
    }

    /**
     * removeMember
     *
     * @param  Conversation $conversation
     * @param  User $memberToRm
     * @return bool
     */
    public function removeMember(Conversation $conversation, User $memberToRm): bool
    {
        if ($this->checkIfUserIsMemberOfConversation($conversation, $memberToRm)) {
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
     * @param  string $conversationName
     * @param  User $user
     * @return bool
     */
    public function changeConversationName(Conversation $conversation, string $conversationName, User $user): bool
    {
        if ($this->checkIfUserIsMemberOfConversation($conversation, $user)) {
            $conversation->setName($conversationName);
            $this->entityManager->flush();

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
     * checkIfUserIsMemberOfConversation
     *
     * @param  Conversation $conversation
     * @param  User $user
     * @return bool
     */
    public function checkIfUserIsMemberOfConversation(Conversation $conversation, User $user): bool
    {
        return in_array($conversation, $user->getConversations()->toArray());
    }

    /**
     * processAttachmentUpload
     *
     * @param  UploadedFile[] $files
     * @param  int $senderId
     * @return string[]
     */
    public function processAttachmentUpload(array $files, int $senderId): array
    {
        $filePaths = [];

        foreach ($files as $file) {
            $pathFormat = match ($file->getClientMimeType()) {
                'image/jpeg'      => '/conversation_attachments/images/%s',
                'image/png'       => '/conversation_attachments/images/%s',
                'text/plain'      => '/conversation_attachments/text_files/%s',
                'application/pdf' => '/conversation_attachments/pdfs/%s'
            };
            $fileName = $this->generateAttachmentName($senderId, $file->getClientOriginalExtension());
            $path     = sprintf($pathFormat, $fileName);
            array_push($filePaths, $path);

            $this->defaultStorage->write($path, $file->getContent());
        }

        return $filePaths;
    }

    /**
     * generateAttachmentName
     *
     * @param  int $senderId
     * @param  string $extension
     * @return string
     */
    public function generateAttachmentName(int $senderId, string $extension): string
    {
        $date            = (new DateTime())->format('dmYHisu');
        $randomNumber    = mt_rand(0, 99999);
        $formattedNumber = sprintf('%05d', $randomNumber);

        return sprintf('%d_%s_%s.%s', $senderId, $date, $formattedNumber, $extension);
    }

    /**
     * processAttachmentsStore
     *
     * @param  UploadedFile[] $files
     * @param  string[] $paths
     * @param  Message $message
     * @return void
     */
    public function processAttachmentsDataStore(array $files, array $paths, Message $message): void
    {
        foreach ($files as $key => $file) {
            $this->messageAttachmentRepository->storeAttachment($file, $paths[$key], $message);
        }
    }
    
    /**
     * getSoloConversationsData
     *
     * @param  User[] $friends
     * @param  User $currentUser
     * @return array
     */
    public function getSoloConversationsData(array $friends, User $currentUser): array
    {
        $conversations = array_map(function ($friend) use($currentUser) {
            return $this->conversationRepository->getFriendConversation($currentUser, $friend);
        }, $friends);

        $conversationsData = [];
        foreach($friends as $key => $friend) {
            array_push(
                $conversationsData, 
                [
                    'friend'       => $friend, 
                    'conversation' => $conversations[$key]
                ]
            );
        }

        return $conversationsData;
    }
}