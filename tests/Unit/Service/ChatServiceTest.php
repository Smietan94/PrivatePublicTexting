<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\Conversation;
use App\Entity\User;
use App\Enum\NotificationType;
use App\Repository\MessageRepository;
use App\Service\ChatService;
use App\Service\NotificationService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Pagerfanta\Pagerfanta;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;

class ChatServiceTest extends TestCase
{
    public function chatServiceProvider(): array
    {
        // Mock MessageRepository, FormFactoryInterface, HubInterface, EntityManagerInterface
        $messageRepositoryMock   = $this->createMock(MessageRepository::class);
        $formFactoryMock         = $this->createMock(FormFactoryInterface::class);
        $entityManagerMock       = $this->createMock(EntityManagerInterface::class);
        $notificationServiceMock = $this->createMock(NotificationService::class);

        // Create an instance of ChatService with the mocks
        $chatService = new ChatService(
            $messageRepositoryMock,
            $formFactoryMock,
            $entityManagerMock,
            $notificationServiceMock
        );

        return [[
            'chatService' => $chatService
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
        $result = $chatService->getMsgPager($request->get('page'), $conversation, 1);

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
        $currentUser  = $this->createMock(User::class);
        $memberToRm   = $this->createMock(User::class);
        $conversation->addConversationMember($currentUser);
        $conversation->addConversationMember($memberToRm);

        $memberToRm
            ->expects($this->once())
            ->method('getId')
            ->willReturn(2137);

        $memberToRm
            ->expects($this->once())
            ->method('getConversations')
            ->willReturn(new ArrayCollection([$conversation]));

        // Call the method to be tested
        $result = $chatService->removeMember(
            NotificationType::REMOVED_FROM_CONVERSATION,
            $conversation,
            $memberToRm,
            $currentUser
        );

        // Assert that the result is true (user was removed)
        $this->assertTrue($result);
    }

    /**
     * @dataProvider chatServiceProvider
     */
    public function testChangeConversationName(ChatService $chatService): void
    {
        // Mock Conversation and User
        $conversation = $this->createMock(Conversation::class);
        $user         = $this->createMock(User::class);

        $conversation
            ->expects($this->once())
            ->method('getName')
            ->willReturn('Old conversation name');

        $user
            ->expects($this->once())
            ->method('getConversations')
            ->willReturn(new ArrayCollection([$conversation]));

        // Call the method to be tested
        $result = $chatService->changeConversationName($conversation, 'New Name', $user);

        // Assert that the result is true (conversation name was changed)
        $this->assertTrue($result);
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

