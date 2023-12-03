<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\FriendHistory;
use App\Entity\User;
use App\Enum\FriendStatus;
use App\Repository\FriendHistoryRepository;
use App\Repository\UserRepository;
use App\Service\FriendsService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class FriendServiceTest extends TestCase
{
    public function FriendsServiceProvider(): array
    {
        $userRepositoryMock          = $this->createMock(UserRepository::class);
        $friendHistoryRepositoryMock = $this->createMock(FriendHistoryRepository::class);
        $entityManagerMock           = $this->createMock(EntityManagerInterface::class);

        return [[
            'FriendsService' => new FriendsService(
                $userRepositoryMock,
                $friendHistoryRepositoryMock,
                $entityManagerMock
            ),
        ]];
    }

    public function FriendsServiceDependencyProvider(): array
    {
        return [[
            'userRepositoryMock'          => $this->createMock(UserRepository::class),
            'friendHistoryRepositoryMock' => $this->createMock(FriendHistoryRepository::class),
            'entityManagerMock'           => $this->createMock(EntityManagerInterface::class)
        ]];
    }

    /**
     * @dataProvider FriendsServiceDependencyProvider
     */
    public function testRemoveFriend(
        UserRepository|MockObject $userRepositoryMock,
        FriendHistoryRepository|MockObject $friendHistoryRepositoryMock,
        EntityManagerInterface|MockObject $entityManagerMock
    ): void {
        $user = new User();
        $friend = new User();
        $friendHistory = new FriendHistory();
        $friendsService = new FriendsService(
            $userRepositoryMock,
            $friendHistoryRepositoryMock,
            $entityManagerMock
        );

        $this->assertFalse($user->getFriends()->contains($friend));
        $user->addFriend($friend);
        $this->assertTrue($friend->getFriends()->contains($user));

        $friendHistory->setRequestedUser($user);
        $friendHistory->setRequestingUser($friend);
        $friendHistory->setStatus(FriendStatus::ACCEPTED->toInt());

        $friendHistoryRepositoryMock
            ->expects($this->once())
            ->method('getFriendHistory')
            ->willReturn($friendHistory);

        $friendsService->removeFriend($user, $friend);

        $this->assertSame(FriendStatus::DELETED->toInt(), $friendHistory->getStatus());
        $this->assertFalse($user->getFriends()->contains($friend));
    }

    /**
     * @dataProvider friendsServiceProvider
     */
    public function testGetReceivedFriendsNames(FriendsService $friendsService): void
    {
        $user           = new User();
        $friend         = new User();
        $friendUserName = 'Felek_kot';
        $friendHistory  = new FriendHistory();

        $friend->setUsername($friendUserName);

        $user->addFriend($friend);
        $friendHistory->setRequestedUser($user);
        $friendHistory->setRequestingUser($friend);
        $friendHistory->setStatus(FriendStatus::ACCEPTED->toInt());

        $result = $friendsService->getReceivedFriendsNames([$friendHistory]);
        $this->assertSame($friendUserName, $result[0]);
    }

    /**
     * @dataProvider friendsServiceProvider
     */
    public function testGetSentFriendsNames(FriendsService $friendsService): void
    {
        $user           = new User();
        $friend         = new User();
        $friendUserName = 'Felek_kot';
        $friendHistory  = new FriendHistory();

        $friend->setUsername($friendUserName);

        $user->addFriend($friend);
        $friendHistory->setRequestingUser($user);
        $friendHistory->setRequestedUser($friend);
        $friendHistory->setStatus(FriendStatus::ACCEPTED->toInt());

        $result = $friendsService->getSentFriendsNames([$friendHistory]);
        $this->assertSame($friendUserName, $result[0]);
    }

    /**
     * @dataProvider friendsServiceProvider
     */
    public function testGetDates(FriendsService $friendsService): void
    {
        $createdAt     = new \DateTime();
        $user          = new User();
        $friend        = new User();
        $friendHistory = new FriendHistory();

        $user->addFriend($friend);
        $friendHistory->setRequestingUser($user);
        $friendHistory->setRequestedUser($friend);
        $friendHistory->setCreatedAt($createdAt);
        $friendHistory->setStatus(FriendStatus::ACCEPTED->toInt());

        $result = $friendsService->getDates([$friendHistory]);
        $this->assertSame($createdAt, $result[0]);
    }

    /**
     * @dataProvider friendsServiceProvider
     */
    public function testGetHowLongFriends(FriendsService $friendsService): void
    {
        $user = new User();

        // creating random dates
        $sentDates = array_map(
            fn() => new \DateTime(
                date('Y-m-d H:i:s',
                rand(926255681, 1236292137)
            )),
            range(0, 2)
        );

        // creating random dates
        $receivedDates = array_map(
            fn() => new \DateTime(
                date('Y-m-d H:i:s',
                rand(926255681, 1236292137)
            )),
            range(0, 2)
        );

        $sentFriend = array_map(
            fn($i) => (new User())->setUsername(sprintf('SentFriend%d', $i)),
        range(0, 2));

        $receivedFriend = array_map(
            fn($i) => (new User())->setUsername(sprintf('ReceivedFriend%d', $i)),
        range(0, 2));

        foreach ($sentFriend as $key => $friend) {
            $user->addFriend($friend);
            $sentFriendHistory = new FriendHistory();
            $sentFriendHistory->setCreatedAt($sentDates[$key]);
            $sentFriendHistory->setRequestedUser($friend);
            $sentFriendHistory->setRequestingUser($user);
            $sentFriendHistory->setStatus(FriendStatus::ACCEPTED->toInt());
            $user->addSentFriendHistory($sentFriendHistory);
        }

        foreach ($receivedFriend as $key => $friend) {
            $user->addFriend($friend);
            $receivedFriendsHistory = new FriendHistory();
            $receivedFriendsHistory->setCreatedAt($receivedDates[$key]);
            $receivedFriendsHistory->setRequestedUser($user);
            $receivedFriendsHistory->setRequestingUser($friend);
            $receivedFriendsHistory->setStatus(FriendStatus::ACCEPTED->toInt());
            $user->addReceivedFriendHistory($receivedFriendsHistory);
        }

        $result = $friendsService->getHowLongFriends($user);

        foreach ($user->getReceivedFriendHistory() as $receivedHistory) {
            $createdAt      = $receivedHistory->getCreatedAt();
            $friendUsername = $receivedHistory->getRequestingUser()->getUsername();

            $this->assertSame($createdAt, $result[$friendUsername]);
        }

        foreach ($user->getSentFriendHistory() as $sentHistory) {
            $createdAt      = $sentHistory->getCreatedAt();
            $friendUsername = $sentHistory->getRequestedUser()->getUsername();

            $this->assertSame($createdAt, $result[$friendUsername]);
        }
    }
}