<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\Conversation;
use App\Entity\Message;
use App\Entity\MessageAttachment;
use App\Entity\User;
use App\Repository\ConversationRepository;
use App\Repository\MessageRepository;
use App\Repository\UserRepository;
use App\Service\MessageAttachmentService;
use App\Service\MessageService;
use App\Service\NotificationService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class MessageServiceTest extends TestCase
{
    public function messageServiceProvider(): array
    {
        $messageService = new MessageService(
            $this->createMock(FormFactoryInterface::class),
            $this->createMock(MessageAttachmentService::class),
            $this->createMock(NotificationService::class),
            $this->createMock(MessageRepository::class),
            $this->createMock(ConversationRepository::class),
            $this->createMock(UserRepository::class),
            $this->createMock(HubInterface::class),
            $this->createMock(ValidatorInterface::class)
        );

        return [['messageService' => $messageService]];
    }

    public function messageServiceMockedDependencyProvider(): array
    {
        return [[
            'formFactoryMock'              => $this->createMock(FormFactoryInterface::class),
            'messageAttachmentServiceMock' => $this->createMock(MessageAttachmentService::class),
            'notificationServiceMock'      => $this->createMock(NotificationService::class),
            'messageRepositoryMock'        => $this->createMock(MessageRepository::class),
            'conversationRepositoryMock'   => $this->createMock(ConversationRepository::class),
            'userRepositoryMock'           => $this->createMock(UserRepository::class),
            'hubMock'                      => $this->createMock(HubInterface::class),
            'validatorMock'                => $this->createMock(ValidatorInterface::class),
            'formMock'                     => $this->createMock(FormInterface::class)
        ]];
    }

    /**
     * @dataProvider messageServiceProvider
     */
    public function testProcessMessageWhenFormIsNotSubmitted(MessageService $messageService): void
    {
        $conversation = new Conversation();
        $request      = new Request();
        $topic        = 'conversation.solo';

        $result = $messageService->processMessage(
            $conversation,
            $request,
            $topic
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('form', $result);
        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('messages', $result);
        $this->assertArrayNotHasKey(1, $result);
    }

    /**
     * @dataProvider messageServiceMockedDependencyProvider
     */
    public function testProcessMessageWhenFormIsSubmittedAndValid(
        FormFactoryInterface|MockObject   $formFactoryMock,
        MessageAttachmentService          $messageAttachmentServiceMock,
        NotificationService               $notificationServiceMock,
        MessageRepository                 $messageRepositoryMock,
        ConversationRepository|MockObject $conversationRepositoryMock,
        UserRepository           $userRepositoryMock,
        HubInterface             $hubMock,
        ValidatorInterface       $validatorMock,
        FormInterface|MockObject $formMock,
    ): void {
        $request        = new Request(); 
        $messageService = new MessageService(
            $formFactoryMock,
            $messageAttachmentServiceMock,
            $notificationServiceMock,
            $messageRepositoryMock,
            $conversationRepositoryMock,
            $userRepositoryMock,
            $hubMock,
            $validatorMock
        );

        $conversationMock = $this->createMock(Conversation::class);

        $formFactoryMock
            ->expects($this->once())
            ->method('create')
            ->willReturn($formMock);

        $formMock
            ->expects($this->once())
            ->method('handleRequest');

        $formMock
            ->expects($this->once())
            ->method('isSubmitted')
            ->willReturn(true);

        $formMock
            ->expects($this->once())
            ->method('isValid')
            ->willReturn(true);

        $formMock
            ->expects($this->once())
            ->method('getData')
            ->willReturn([
                'senderId'       => 2137,
                'message'        => 'siema siema, kazdy o tej porze wypic sobie moze',
                'conversationId' => 42
            ]);

        $conversationMock
            ->expects($this->exactly(2))
            ->method('getId')
            ->willReturn(42);

        $result = $messageService->processMessage(
            $conversationMock,
            $request,
            'conversation.priv'
        );

        $this->assertSame(true, $result['success']);
        $this->assertIsArray($result);
        $this->assertEmpty($result['messages']);
    }

    /**
     * @dataProvider messageServiceMockedDependencyProvider
     */
    public function testProcessMessageWhenFormIsSubmittedAndNotValid(
        FormFactoryInterface|MockObject   $formFactoryMock,
        MessageAttachmentService          $messageAttachmentServiceMock,
        NotificationService               $notificationServiceMock,
        MessageRepository                 $messageRepositoryMock,
        ConversationRepository|MockObject $conversationRepositoryMock,
        UserRepository           $userRepositoryMock,
        HubInterface             $hubMock,
        ValidatorInterface       $validatorMock,
        FormInterface|MockObject $formMock,
    ): void {
        $request        = new Request();
        $conversation   = new Conversation();
        $messageService = new MessageService(
            $formFactoryMock,
            $messageAttachmentServiceMock,
            $notificationServiceMock,
            $messageRepositoryMock,
            $conversationRepositoryMock,
            $userRepositoryMock,
            $hubMock,
            $validatorMock
        );

        $formFactoryMock
            ->expects($this->once())
            ->method('create')
            ->willReturn($formMock);

        $formMock
            ->expects($this->once())
            ->method('handleRequest');

        $formMock
            ->expects($this->exactly(2))
            ->method('isSubmitted')
            ->willReturn(true);

        $formMock
            ->expects($this->exactly(2))
            ->method('isValid')
            ->willReturn(false);

        $result = $messageService->processMessage(
            $conversation,
            $request,
            'conversation.priv'
        );

        $this->assertIsArray($result);
        $this->assertSame(false, $result['success']);
    }

    /**
     * @dataProvider messageServiceProvider
     */
    public function testProcessGroupCreationWhenFormIsNotSubmitted(MessageService $messageService): void
    {
        $request     = new Request();
        $currentUser = new User();

        $result = $messageService->processGroupCreation(
            $request,
            $currentUser
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('form', $result);
        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('messages', $result);
        $this->assertArrayNotHasKey('conversationId', $result);
        $this->assertArrayNotHasKey(1, $result);
    }

    /**
     * @dataProvider messageServiceMockedDependencyProvider
     */
    public function testProcessGroupCreationWhenFormIsSubmittedAndValid(
        FormFactoryInterface|MockObject   $formFactoryMock,
        MessageAttachmentService          $messageAttachmentServiceMock,
        NotificationService               $notificationServiceMock,
        MessageRepository                 $messageRepositoryMock,
        ConversationRepository|MockObject $conversationRepositoryMock,
        UserRepository           $userRepositoryMock,
        HubInterface             $hubMock,
        ValidatorInterface       $validatorMock,
        FormInterface|MockObject $formMock,
    ): void {
        $request        = new Request();
        $user           = new User();
        $messageService = new MessageService(
            $formFactoryMock,
            $messageAttachmentServiceMock,
            $notificationServiceMock,
            $messageRepositoryMock,
            $conversationRepositoryMock,
            $userRepositoryMock,
            $hubMock,
            $validatorMock
        );

        $conversationMock = $this->createMock(Conversation::class);

        $formFactoryMock
            ->expects($this->once())
            ->method('create')
            ->willReturn($formMock);

        $formMock
            ->expects($this->once())
            ->method('handleRequest');

        $formMock
            ->expects($this->once())
            ->method('isSubmitted')
            ->willReturn(true);

        $formMock
            ->expects($this->once())
            ->method('isValid')
            ->willReturn(true);

        $formMock
            ->expects($this->once())
            ->method('getData')
            ->willReturn([
                'friends' => array_map(
                    fn() => new User(),
                    range(0, 3)
                ),
                'conversationName' => 'KlusiasFriends',
                'senderId'         => 2137,
                'message'          => 'siema siema'
            ]);

        $conversationRepositoryMock
            ->expects($this->once())
            ->method('storeConversation')
            ->willReturn($conversationMock);

        $conversationMock
            ->expects($this->exactly(2))
            ->method('getId')
            ->willReturn(21);

        $result = $messageService->processGroupCreation(
            $request,
            $user
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('form', $result);
        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('messages', $result);
        $this->assertArrayHasKey('conversationId', $result);
        $this->assertArrayNotHasKey(1, $result);
        $this->assertEquals(21, $result['conversationId']);
    }

    /**
     * @dataProvider messageServiceMockedDependencyProvider
     */
    public function testProcessGroupCreationWhenFormIsSubmittedAndNotValid(
        FormFactoryInterface|MockObject   $formFactoryMock,
        MessageAttachmentService          $messageAttachmentServiceMock,
        NotificationService               $notificationServiceMock,
        MessageRepository                 $messageRepositoryMock,
        ConversationRepository|MockObject $conversationRepositoryMock,
        UserRepository           $userRepositoryMock,
        HubInterface             $hubMock,
        ValidatorInterface       $validatorMock,
        FormInterface|MockObject $formMock,
    ): void {
        $request        = new Request();
        $user           = new User();
        $messageService = new MessageService(
            $formFactoryMock,
            $messageAttachmentServiceMock,
            $notificationServiceMock,
            $messageRepositoryMock,
            $conversationRepositoryMock,
            $userRepositoryMock,
            $hubMock,
            $validatorMock
        );

        $formFactoryMock
            ->expects($this->once())
            ->method('create')
            ->willReturn($formMock);

        $formMock
            ->expects($this->once())
            ->method('handleRequest');

        $formMock
            ->expects($this->exactly(2))
            ->method('isSubmitted')
            ->willReturn(true);

        $formMock
            ->expects($this->exactly(2))
            ->method('isValid')
            ->willReturn(false);

        $result = $messageService->processGroupCreation(
            $request,
            $user
        );

        $this->assertIsArray($result);
        $this->assertSame(false, $result['success']);
    }

    /**
     * @dataProvider messageServiceMockedDependencyProvider
     */
    public function testProcessSuccedData(
        FormFactoryInterface|MockObject   $formFactoryMock,
        MessageAttachmentService          $messageAttachmentServiceMock,
        NotificationService               $notificationServiceMock,
        MessageRepository                 $messageRepositoryMock,
        ConversationRepository|MockObject $conversationRepositoryMock,
        UserRepository           $userRepositoryMock,
        HubInterface             $hubMock,
        ValidatorInterface       $validatorMock
    ): void {
        $attachmentMock   = $this->createMock(UploadedFile::class);
        $conversationMock = $this->createMock(Conversation::class);
        $data             = [
            'attachment' => [],
            'senderId'   => 2137,
            'message'    => 'siema siema, kazdy o tej porze wypic sobie moze',
            'attachment' => [$attachmentMock]
        ];
        $messageService   = new MessageService(
            $formFactoryMock,
            $messageAttachmentServiceMock,
            $notificationServiceMock,
            $messageRepositoryMock,
            $conversationRepositoryMock,
            $userRepositoryMock,
            $hubMock,
            $validatorMock
        );

        $conversationMock
            ->expects($this->exactly(2))
            ->method('getId')
            ->willReturn(42);

        $result = $messageService->processSuccedData(
            $data,
            $conversationMock
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('attachmentPaths', $result);
        $this->assertArrayHasKey('attachmentsIds', $result);
    }

    /**
     * @dataProvider messageServiceMockedDependencyProvider
     */
    public function testMessageFailure(
        FormFactoryInterface|MockObject   $formFactoryMock,
        MessageAttachmentService          $messageAttachmentServiceMock,
        NotificationService               $notificationServiceMock,
        MessageRepository                 $messageRepositoryMock,
        ConversationRepository|MockObject $conversationRepositoryMock,
        UserRepository           $userRepositoryMock,
        HubInterface             $hubMock,
        ValidatorInterface       $validatorMock,
        FormInterface|MockObject $formMock,
    ): void {
        $result = [];
        $messageService   = new MessageService(
            $formFactoryMock,
            $messageAttachmentServiceMock,
            $notificationServiceMock,
            $messageRepositoryMock,
            $conversationRepositoryMock,
            $userRepositoryMock,
            $hubMock,
            $validatorMock
        );

        $formFactoryMock
            ->expects($this->once())
            ->method('create')
            ->willReturn($formMock);

        $result = $messageService->messageFailure($formMock, $result);

        $this->assertSame($result['success'], false);
        $this->assertSame($result['form'], $formMock);
    }

    /** 
     * @dataProvider messageServiceMockedDependencyProvider
     */
    public function testProcessAttachments(
        FormFactoryInterface|MockObject     $formFactoryMock,
        MessageAttachmentService|MockObject $messageAttachmentServiceMock,
        NotificationService               $notificationServiceMock,
        MessageRepository      $messageRepositoryMock,
        ConversationRepository $conversationRepositoryMock,
        UserRepository         $userRepositoryMock,
        HubInterface           $hubMock,
        ValidatorInterface     $validatorMock,
    ): void {
        $message        = new Message();
        $messageService = new MessageService(
            $formFactoryMock,
            $messageAttachmentServiceMock,
            $notificationServiceMock,
            $messageRepositoryMock,
            $conversationRepositoryMock,
            $userRepositoryMock,
            $hubMock,
            $validatorMock
        );

        $uploadedFile1 = $this->createMock(UploadedFile::class);
        $uploadedFile2 = $this->createMock(UploadedFile::class);

        $attachment1 = $this->createMock(MessageAttachment::class);
        $attachment2 = $this->createMock(MessageAttachment::class);

        $attachment1
            ->expects($this->once())
            ->method('getId')
            ->willReturn(2137);

        $attachment2
            ->expects($this->once())
            ->method('getId')
            ->willReturn(2138);

        $uploadedFilesArray = [
            $uploadedFile1,
            $uploadedFile2
        ];

        $attachmentsArray = [
            $attachment1,
            $attachment2
        ];

        $attachmentsPaths = [
            'attachmentPath1',
            'attachmentPath2'
        ];

        $messageAttachmentServiceMock
            ->expects($this->once())
            ->method('processAttachmentsDataStore')
            ->willReturn($attachmentsArray);

        $result = $messageService->processAttachments(
            $uploadedFilesArray,
            $attachmentsPaths,
            $message
        );

        $this->assertIsArray($result);
        $this->assertContainsOnly('int', $result);
    }
}
