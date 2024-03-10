<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\Conversation;
use App\Entity\FriendHistory;
use App\Entity\FriendRequest;
use App\Entity\User;
use App\Enum\FriendStatus;
use App\Repository\ConversationRepository;
use App\Repository\FriendHistoryRepository;
use App\Repository\FriendRequestRepository;
use App\Service\FriendRequestService;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class FriendRequestServiceTest extends TestCase
{
    public function friendRequestServiceMockDependencyProvider(): array
    {
        return [[
            'entityManagerMock'           => $this->createMock(EntityManagerInterface::class),
            'friendRequestRepositoryMock' => $this->createMock(FriendRequestRepository::class),
            'friendHistoryRepositoryMock' => $this->createMock(FriendHistoryRepository::class),
            'conversationRepositoryMock'  => $this->createMock(ConversationRepository::class),
            'notificationServiceMock'     => $this->createMock(NotificationService::class)
        ]];
    }

    public function friendRequestServiceProvider(): array
    {
        $entityManagerMock           = $this->createMock(EntityManagerInterface::class);
        $friendRequestRepositoryMock = $this->createMock(FriendRequestRepository::class);
        $friendHistoryRepositoryMock = $this->createMock(FriendHistoryRepository::class);
        $conversationRepositoryMock  = $this->createMock(ConversationRepository::class);
        $notificationServiceMock     = $this->createMock(NotificationService::class);

        return [[
            'friendRequestService' => new FriendRequestService(
                $entityManagerMock,
                $friendRequestRepositoryMock,
                $friendHistoryRepositoryMock,
                $conversationRepositoryMock,
                $notificationServiceMock
            ),
        ]];
    }

    /**
     * @dataProvider friendRequestServiceMockDependencyProvider
     */
    public function testAcceptRequest(
        EntityManagerInterface  $entityManagerMock,
        FriendRequestRepository $friendRequestRepositoryMock,
        FriendHistoryRepository $friendHistoryRepositoryMock,
        ConversationRepository  $conversationRepositoryMock,
        NotificationService     $notificationServiceMock
    ): void {
        $currentUser          = new User();
        $requestingUser       = new User();
        $friendRequest        = new FriendRequest();
        $conversation         = $this->createMock(Conversation::class);
        $friendRequestService = new FriendRequestService(
            $entityManagerMock,
            $friendRequestRepositoryMock,
            $friendHistoryRepositoryMock,
            $conversationRepositoryMock,
            $notificationServiceMock
        );

        $conversationRepositoryMock
            ->expects($this->exactly(2))
            ->method('getFriendConversation')
            ->willReturn($conversation);

        $conversation
            ->expects($this->once())
            ->method('getId')
            ->willReturn(2137);

        $friendRequest->setRequestedUser($currentUser);
        $friendRequest->setRequestingUser($requestingUser);
        $friendRequest->setCreatedAt(new \DateTime());

        $result = $friendRequestService->acceptRequest(
            $currentUser,
            $friendRequest,
            FriendStatus::ACCEPTED->toInt()
        );

        $this->assertInstanceOf(FriendHistory::class, $result);
    }

    /**
     * @dataProvider friendRequestServiceProvider
     */
    public function testDeleteRequestAndSetHisotory(FriendRequestService $friendRequestService): void
    {
        $currentUser    = new User();
        $requestingUser = new User();
        $friendRequest  = new FriendRequest();

        $friendRequest->setRequestedUser($currentUser);
        $friendRequest->setRequestingUser($requestingUser);
        $friendRequest->setCreatedAt(new \DateTime());

        $result = $friendRequestService->deleteRequestAndSetHistory($friendRequest, FriendStatus::REJECTED->toInt());

        $this->assertInstanceOf(FriendHistory::class, $result);
    }
}