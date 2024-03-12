<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\Message;
use App\Entity\Notification;
use App\Entity\User;
use App\Enum\NotificationType;
use PHPUnit\Framework\TestCase;

class NotificationTest extends TestCase
{
    public function notificationTypeProvider()
    {
        foreach(NotificationType::cases() as $notificationType) {
            yield [$notificationType];
        }
    }

    public function testSetAndGetSender(): void
    {
        $notification = new Notification();
        $sender       = new User();
        $testUser     = new User();

        $this->assertNull($notification->getSender());

        $notification->setSender($sender);

        $this->assertInstanceOf(User::class, $notification->getSender());
        $this->assertSame($sender, $notification->getSender());
        $this->assertNotSame($testUser, $notification->getSender());
    }

    public function testSetAndGetReceiver(): void
    {
        $notification = new Notification();
        $receiver     = new User();
        $testUser     = new User();

        $this->assertNull($notification->getReceiver());

        $notification->setReceiver($receiver);

        $this->assertInstanceOf(User::class, $notification->getReceiver());
        $this->assertSame($receiver, $notification->getReceiver());
        $this->assertNotSame($testUser, $notification->getReceiver());
    }

    public function testSetAndGetDisplayStatus(): void
    {
        $notification = new Notification();

        $this->assertNull($notification->isDisplayed());

        $notification->setDisplayed(false);

        $this->assertFalse($notification->isDisplayed());

        $notification->setDisplayed(true);

        $this->assertIsBool($notification->isDisplayed());
        $this->assertTrue($notification->isDisplayed());
    }

    /**
     * @dataProvider notificationTypeProvider
     */
    public function testSetAndGetMessage(NotificationType $notificationType): void
    {
        $notification = new Notification();
        $message      = $notificationType->getMessage();
        $testMessage  = 'test message different from provided in enum';

        $this->assertNull($notification->getMessage());

        $notification->setMessage($message);

        $this->assertIsString($notification->getMessage());
        $this->assertSame($message, $notification->getMessage());
        $this->assertNotSame($testMessage, $notification->getMessage());
    }

    public function testSetAndGetConversationId(): void
    {
        $notification   = new Notification();
        $conversationId = 2137;
        $testId         = 2138;

        $this->assertNull($notification->getConversationId());

        $notification->setConversationId($conversationId);

        $this->assertIsInt($notification->getConversationId());
        $this->assertSame($conversationId, $notification->getConversationId());
        $this->assertNotSame($testId, $notification->getConversationId());
    }
}