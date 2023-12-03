<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\Conversation;
use App\Entity\User;
use App\Repository\MessageRepository;
use App\Service\ChatService;
use Doctrine\ORM\EntityManagerInterface;
use Pagerfanta\Pagerfanta;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mercure\HubInterface;
use TypeError;

class ChatServiceTest extends TestCase
{
    public function chatServiceProvider(): array
    {
        // Mock MessageRepository, FormFactoryInterface, HubInterface, EntityManagerInterface
        $messageRepositoryMock = $this->createMock(MessageRepository::class);
        $formFactoryMock       = $this->createMock(FormFactoryInterface::class);
        $hubMock               = $this->createMock(HubInterface::class);
        $entityManagerMock     = $this->createMock(EntityManagerInterface::class);

        // Create an instance of ChatService with the mocks
        $chatService = new ChatService($messageRepositoryMock, $formFactoryMock, $hubMock, $entityManagerMock);

        return [[
            'chatService' => $chatService
        ]];
    }

    public function chatServiceMockedDependencyProvider(): array
    {
        // Mock MessageRepository, FormFactoryInterface, HubInterface, EntityManagerInterface
        return [[
            'messageRepositoryMock' => $this->createMock(MessageRepository::class),
            'formFactoryMock'       => $this->createMock(FormFactoryInterface::class),
            'hubMock'               => $this->createMock(HubInterface::class),
            'entityManagerMock'     => $this->createMock(EntityManagerInterface::class),
            'formMock'              => $this->createMock(FormInterface::class)
        ]];
    }

    /**
     * @dataProvider chatServiceProvider
     */
    public function testGetMsgPager(ChatService $chatService): void
    {
        // Mock Conversation and Request
        $conversation = new \App\Entity\Conversation();
        $message      = new \App\Entity\Message();
        $request      = new Request(['page' => 1]);
        $conversation->addMessage($message);

        // Call the method to be tested
        $result = $chatService->getMsgPager($request, $conversation, 1);

        // Assert that the result is an instance of Pagerfanta
        $this->assertInstanceOf(Pagerfanta::class, $result);
    }

    /**
     * @dataProvider chatServiceProvider
     */
    public function testRemoveMember(ChatService $chatService): void
    {
        // Mock Conversation and User
        $conversation = new Conversation();
        $memberToRm   = new User();
        $conversation->addConversationMember($memberToRm);

        // Call the method to be tested
        $result = $chatService->removeMember($conversation, $memberToRm);

        // Assert that the result is true (user was removed)
        $this->assertTrue($result);
    }

    /**
     * @dataProvider chatServiceProvider
     */
    public function testChangeConversationName(ChatService $chatService): void
    {
        // Mock Conversation and User
        $conversation = new Conversation();
        $user         = new User();
        $conversation->addConversationMember($user);

        // Call the method to be tested
        $result = $chatService->changeConversationName($conversation, 'New Name', $user);

        // Assert that the result is true (conversation name was changed)
        $this->assertTrue($result);
    }

    /**
     * @dataProvider chatServiceMockedDependencyProvider
     */
    public function testProcessMessageWithValidData(
        MessageRepository $messageRepositoryMock,
        FormFactoryInterface|MockObject $formFactoryMock,
        HubInterface|MockObject $hubMock,
        EntityManagerInterface $entityManagerMock,
        FormInterface|MockObject $formMock
    ): void {
        $topic        = 'conversation';
        $conversation = new Conversation();
        $request      = new Request();
        $chatService  = new ChatService(
            $messageRepositoryMock,
            $formFactoryMock,
            $hubMock,
            $entityManagerMock
        );

        $formFactoryMock
            ->expects($this->exactly(2))
            ->method('create')
            ->willReturnOnConsecutiveCalls($formMock, $formMock);

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
            ->willReturn(['senderId' => 1, 'message' => 'Hello, world!']);

        $hubMock
            ->expects($this->once())
            ->method('publish');

        $messageRepositoryMock
            ->expects($this->once())
            ->method('storeMessage');

        $result = $chatService->processMessage($conversation, $request, $topic);

        $this->assertInstanceOf(FormInterface::class, $result);
    }

    /**
     * @dataProvider chatServiceMockedDependencyProvider
     */
    public function testProcessMessageWithNotValidData(
        MessageRepository $messageRepositoryMock,
        FormFactoryInterface|MockObject $formFactoryMock,
        HubInterface|MockObject $hubMock,
        EntityManagerInterface $entityManagerMock,
        FormInterface|MockObject $formMock
    ): void {
        $chatService  = new ChatService($messageRepositoryMock, $formFactoryMock, $hubMock, $entityManagerMock );
        $conversation = new Conversation();
        $request      = new Request();
        $topic        = 'conversation';

        $formFactoryMock
            ->expects($this->exactly(2))
            ->method('create')
            ->willReturnOnConsecutiveCalls($formMock, $formMock);

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

        $this->expectException(TypeError::class);
        $formMock
            ->expects($this->once())
            ->method('getData')
            ->willReturn(['senderId' => '1', 'message' => 2137]);

        $hubMock
            ->expects($this->once())
            ->method('publish');

        $messageRepositoryMock
            ->expects($this->once())
            ->method('storeMessage');

        $result = $chatService->processMessage($conversation, $request, $topic);

        $this->assertInstanceOf(FormInterface::class, $result);
    }

    /**
     * @dataProvider chatServiceMockedDependencyProvider
     */
    public function testProcessMessageWithNullData(
        MessageRepository $messageRepositoryMock,
        FormFactoryInterface|MockObject $formFactoryMock,
        HubInterface|MockObject $hubMock,
        EntityManagerInterface $entityManagerMock,
        FormInterface|MockObject $formMock
    ): void {
        $chatService  = new ChatService($messageRepositoryMock, $formFactoryMock, $hubMock, $entityManagerMock );
        $conversation = new Conversation();
        $request      = new Request();
        $topic        = 'conversation';

        $formFactoryMock
            ->expects($this->exactly(2))
            ->method('create')
            ->willReturnOnConsecutiveCalls($formMock, $formMock);

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

        $this->expectException(TypeError::class);
        $formMock
            ->expects($this->once())
            ->method('getData')
            ->willReturn(['senderId' => null, 'message' => null]);

        $hubMock
            ->expects($this->once())
            ->method('publish');

        $messageRepositoryMock
            ->expects($this->once())
            ->method('storeMessage');

        $result = $chatService->processMessage($conversation, $request, $topic);

        $this->assertInstanceOf(FormInterface::class, $result);
    }

    /**
     * @dataProvider chatServiceProvider
     */
    public function testCheckIfUserIsMemberOfConversation(ChatService $chatService): void
    {
        $conversation = new Conversation();
        $user         = new User();

        $this->assertFalse($chatService->checkIfUserIsMemberOfConversation($conversation, $user));

        $conversation->addConversationMember($user);

        $this->assertTrue($chatService->checkIfUserIsMemberOfConversation($conversation, $user));
    }
}

