<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\Conversation;
use App\Entity\Message;
use App\Entity\MessageAttachment;
use PHPUnit\Framework\TestCase;

class MessageTest extends TestCase
{
    public function testSetAndGetMessage(): void
    {
        $message = new Message();

        $this->assertNull($message->getMessage());

        $message->setMessage('Hello, world!');
        $this->assertSame('Hello, world!', $message->getMessage());
    }

    public function testSetAndGetSenderId(): void
    {
        $message = new Message();

        $this->assertNull($message->getSenderId());

        $message->setSenderId(2137);
        $this->assertSame(2137, $message->getSenderId());
    }

    public function testSetAndGetConversation(): void
    {
        $message = new Message();

        $this->assertNull($message->getConversation());

        $conversation = new Conversation();
        $message->setConversation($conversation);
        $this->assertSame($conversation, $message->getConversation());
    }

    public function testTimestamps(): void
    {
        $message = new Message();

        $this->assertNull($message->getCreatedAt());
        $this->assertNull($message->getUpdatedAt());

        $message->setCreatedAt(new \DateTime());
        $message->setUpdatedAt(new \DateTime());

        $this->assertInstanceOf(\DateTimeInterface::class, $message->getCreatedAt());
        $this->assertInstanceOf(\DateTimeInterface::class, $message->getUpdatedAt());
    }

    public function testAddAndGetMessageAttachment(): void
    {
        $message            = new Message();
        $messageAttachment1 = new MessageAttachment();
        $messageAttachment2 = new MessageAttachment();

        $message->setAttachment(true);
        $message->addMessageAttachment($messageAttachment1);
        $message->addMessageAttachment($messageAttachment2);

        $this->assertTrue($message->isAttachment());
        $this->assertSame(count($message->getMessageAttachments()), 2);
    }
}