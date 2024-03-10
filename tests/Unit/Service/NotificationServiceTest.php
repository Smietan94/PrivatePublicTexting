<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\Conversation;
use App\Entity\Notification;
use App\Entity\User;
use App\Enum\NotificationType;
use App\Repository\NotificationRepository;
use App\Service\NotificationService;
use App\Twig\Runtime\ConversationMemberRuntime;
use Doctrine\Common\Collections\ArrayCollection;
use Exception;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Mercure\HubInterface;

class NotificationServiceTest extends TestCase
{
    public function notificationServiceMockDependencyProvider(): array
    {
        return [[
            'conversationProcessorMock'  => $this->createMock(ConversationMemberRuntime::class),
            'hubMock'                    => $this->createMock(HubInterface::class),
            'notificationRepositoryMock' => $this->createMock(NotificationRepository::class),
            'formFactoryMock'            => $this->createMock(FormFactoryInterface::class)
        ]];
    }

    public function notificationServiceProvider(): array
    {
        return [[
            'notificationService' => new NotificationService(
                $this->createMock(ConversationMemberRuntime::class),
                $this->createMock(HubInterface::class),
                $this->createMock(NotificationRepository::class),
                $this->createMock(FormFactoryInterface::class)
            )
        ]];
    }

    /**
     * @dataProvider notificationServiceProvider
     */
    public function testSortReceivedNotificationsCollectionOrderByDate(NotificationService $notificationService): void
    {
        $params = [
            'order'             => 'ASC',
            'notificationTypes' => []
        ];

        $notification1 = $this->createMock(Notification::class);
        $notification2 = $this->createMock(Notification::class);
        $notification3 = $this->createMock(Notification::class);
        $notification4 = $this->createMock(Notification::class);
        $notification5 = $this->createMock(Notification::class);

        $notificationsCollection = new ArrayCollection([
            $notification1,
            $notification2,
            $notification3,
            $notification4,
            $notification5
        ]);

        $expects = count($notificationsCollection) - 1;

        $notification1
            ->expects($this->exactly($expects))
            ->method('getCreatedAt')
            ->willReturn((new \DateTime())->setTime(12, 30));

        $notification2
            ->expects($this->exactly($expects))
            ->method('getCreatedAt')
            ->willReturn((new \DateTime())->setTime(12, 35));

        $notification3
            ->expects($this->exactly($expects))
            ->method('getCreatedAt')
            ->willReturn((new \DateTime())->setTime(10, 31));

        $notification4
            ->expects($this->exactly($expects))
            ->method('getCreatedAt')
            ->willReturn((new \DateTime())->setTime(9, 22));

        $notification5
            ->expects($this->exactly($expects))
            ->method('getCreatedAt')
            ->willReturn((new \DateTime())->setTime(9, 42));

        $result = $notificationService->sortReceivedNotificationsCollection($notificationsCollection, $params);

        $this->assertIsArray($result);
        $this->assertContainsOnlyInstancesOf(Notification::class, $result);
        $this->assertSame($notification4, array_values($result)[0]);
        $this->assertSame($notification1, array_values($result)[3]);
    }

    /**
     * @dataProvider notificationServiceProvider
     */
    public function testSortReceivedNotificationsCollectionFilterByNotificationType(NotificationService $notificationService): void
    {
        $params = [
            'order'             => 'DESC',
            'notificationTypes' => [
                NotificationType::ADDED_TO_CONVERSATION->toInt(),
                NotificationType::FRIEND_REQUEST_RECEIVED->toInt()
            ]
        ];

        $notification1 = $this->createMock(Notification::class);
        $notification2 = $this->createMock(Notification::class);
        $notification3 = $this->createMock(Notification::class);
        $notification4 = $this->createMock(Notification::class);
        $notification5 = $this->createMock(Notification::class);

        $notificationsCollection = new ArrayCollection([
            $notification1,
            $notification2,
            $notification3,
            $notification4,
            $notification5
        ]);

        $notification1
            ->expects($this->once())
            ->method('getNotificationType')
            ->willReturn(NotificationType::ADDED_TO_CONVERSATION->toInt());

        $notification2
            ->expects($this->once())
            ->method('getNotificationType')
            ->willReturn(NotificationType::FRIEND_REQUEST_DENIED->toInt());

        $notification3
            ->expects($this->once())
            ->method('getNotificationType')
            ->willReturn(NotificationType::REMOVED_CONVERSATION->toInt());

        $notification4
            ->expects($this->once())
            ->method('getNotificationType')
            ->willReturn(NotificationType::FRIEND_REQUEST_RECEIVED->toInt());

        $notification5
            ->expects($this->once())
            ->method('getNotificationType')
            ->willReturn(NotificationType::ADDED_TO_CONVERSATION->toInt());

        $notification1
            ->expects($this->exactly(2))
            ->method('getCreatedAt')
            ->willReturn((new \DateTime())->setTime(12, 30));

        $notification4
            ->expects($this->exactly(2))
            ->method('getCreatedAt')
            ->willReturn((new \DateTime())->setTime(9, 22));

        $notification5
            ->expects($this->exactly(2))
            ->method('getCreatedAt')
            ->willReturn((new \DateTime())->setTime(9, 42));

        $result = $notificationService->sortReceivedNotificationsCollection($notificationsCollection, $params);

        $this->assertIsArray($result);
        $this->assertContainsOnlyInstancesOf(Notification::class, $result);
        $this->assertSame($notification4, array_values($result)[2]);
        $this->assertSame($notification1, array_values($result)[0]);
    }

    /**
     * @dataProvider notificationServiceProvider
     */
    public function testProcessConversationMemberRemoveNotification(NotificationService $notificationService): void
    {
        $currentUser         = $this->createMock(User::class);
        $removedUser         = $this->createMock(User::class);
        $conversationMember1 = $this->createMock(User::class);
        $conversationMember2 = $this->createMock(User::class);
        $conversation        = $this->createMock(Conversation::class);
        $conversationMembers = [
            $currentUser,
            $removedUser,
            $conversationMember1,
            $conversationMember2
        ];

        $currentUser
            ->expects($this->once())
            ->method('getUsername')
            ->willReturn('currentUser');

        $removedUser
            ->expects($this->once())
            ->method('getUsername')
            ->willReturn('removeduser');

        $conversation
            ->expects($this->once())
            ->method('getName')
            ->willReturn('testConversation');

        $conversation
            ->expects($this->once())
            ->method('getId')
            ->willReturn(2137);

        $conversation
            ->expects($this->once())
            ->method('getConversationMembers')
            ->willReturn(new ArrayCollection($conversationMembers));

        $result = $notificationService->processConversationMemberRemoveNotification(
            $currentUser,
            $removedUser,
            $conversation
        );

        $this->assertIsArray($result);
        $this->assertContainsOnlyInstancesOf(Notification::class, $result);
        $this->assertSame(count($result), count($conversationMembers));
    }

    /**
     * @dataProvider notificationServiceProvider
     */
    public function testProcessNameChangeNotification(NotificationService $notificationService): void
    {
        $oldCoversationName  = 'oldConversationName';
        $currentUser         = $this->createMock(User::class);
        $conversationMember1 = $this->createMock(User::class);
        $conversationMember2 = $this->createMock(User::class);
        $conversation        = $this->createMock(Conversation::class);

        $conversationMembers = [
            $currentUser,
            $conversationMember1,
            $conversationMember2
        ];

        $currentUser
            ->expects($this->once())
            ->method('getUsername')
            ->willReturn('CurrentUser');

        $conversation
            ->expects($this->once())
            ->method('getName')
            ->willReturn('newConversationName');

        $conversation
            ->expects($this->once())
            ->method('getId')
            ->willReturn(2137);

        $conversation
            ->expects($this->once())
            ->method('getConversationMembers')
            ->willReturn(new ArrayCollection($conversationMembers));

        $result = $notificationService->processNameChangeNotification(
            $currentUser,
            $conversation,
            $oldCoversationName
        );

        $this->assertIsArray($result);
        $this->assertContainsOnlyInstancesOf(Notification::class, $result);
        $this->assertSame(count($result), count($conversationMembers));
    }

    /**
     * @dataProvider notificationServiceProvider
     */
    public function testProcessNewConversationMemberAddition(NotificationService $notificationService): void
    {
        $currentUser         = $this->createMock(User::class);
        $newMember1          = $this->createMock(User::class);
        $newMember2          = $this->createMock(User::class);
        $newMember3          = $this->createMock(User::class);
        $conversationMember1 = $this->createMock(User::class);
        $conversationMember2 = $this->createMock(User::class);
        $conversationMember3 = $this->createMock(User::class);
        $conversation        = $this->createMock(Conversation::class);
        $newMembers          = [
            $newMember1,
            $newMember2,
            $newMember3,
        ];
        $conversationMembers = [
            $currentUser,
            $newMember1,
            $newMember2,
            $newMember3,
            $conversationMember1,
            $conversationMember2,
            $conversationMember3,
        ];

        $currentUser
            ->expects($this->exactly(2))
            ->method('getUsername')
            ->willReturn('currentUser');

        $newMember1
            ->expects($this->exactly(2))
            ->method('getUsername')
            ->willReturn('newMember1');

        $newMember2
            ->expects($this->exactly(2))
            ->method('getUsername')
            ->willReturn('newMember2');

        $newMember3
            ->expects($this->exactly(2))
            ->method('getUsername')
            ->willReturn('newMember3');

        $conversationMember1
            ->expects($this->once())
            ->method('getUsername')
            ->willReturn('conversationMember1');

        $conversationMember2
            ->expects($this->once())
            ->method('getUsername')
            ->willReturn('conversationMember2');

        $conversationMember3
            ->expects($this->once())
            ->method('getUsername')
            ->willReturn('conversationMember3');

        $conversation
            ->expects($this->once())
            ->method('getName')
            ->willReturn('conversationName');

        $conversation
            ->expects($this->once())
            ->method('getId')
            ->willReturn(2137);

        $conversation
            ->expects($this->once())
            ->method('getConversationMembers')
            ->willReturn(new ArrayCollection($conversationMembers));

        $result = $notificationService->processNewConversationMemberAdditionNotification(
            $currentUser,
            $newMembers,
            $conversation
        );

        $this->assertIsArray($result);
        $this->assertContainsOnlyInstancesOf(Notification::class, $result);
        $this->assertSame(count($result), count($conversationMembers));
    }

    /**
     * @dataProvider notificationServiceProvider
     */
    public function testProcessFriendStatusNotificationWhenAccept(NotificationService $notificationService): void
    {
        $notificationType = NotificationType::FRIEND_REQUEST_ACCEPTED;
        $sender           = $this->createMock(User::class);
        $receiver         = $this->createMock(User::class);
        $conversationId   = 2137;

        $result = $notificationService->processFriendStatusNotification(
            $notificationType,
            $sender,
            $receiver,
            $conversationId
        );

        $this->assertIsArray($result);
        $this->assertEquals(2, count($result));
        $this->assertContainsOnlyInstancesOf(Notification::class, $result);
    }

    /**
     * @dataProvider notificationServiceProvider
     */
    public function testProcessFriendStatusNotificationWhenOtherValidType(NotificationService $notificationService): void
    {
        $notificationType = NotificationType::FRIEND_REQUEST_RECEIVED;
        $sender           = $this->createMock(User::class);
        $receiver         = $this->createMock(User::class);
        $conversationId   = 2137;

        $result = $notificationService->processFriendStatusNotification(
            $notificationType,
            $sender,
            $receiver,
            $conversationId
        );

        $this->assertInstanceOf(Notification::class, $result);
    }

    /**
     * @dataProvider notificationServiceProvider
     */
    public function testProcessFriendStatusNotificationWhenInvalidType(NotificationService $notificationService): void
    {
        $notificationType = NotificationType::CONVERSATION_GROUP_CREATED;
        $sender           = $this->createMock(User::class);
        $receiver         = $this->createMock(User::class);
        $conversationId   = 2137;

        $this->expectException(Exception::class);
        $this->expectExceptionCode(400);

        $result = $notificationService->processFriendStatusNotification(
            $notificationType,
            $sender,
            $receiver,
            $conversationId
        );
    }
}