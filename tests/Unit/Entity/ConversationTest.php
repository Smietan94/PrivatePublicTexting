<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\Conversation;
use App\Entity\Message;
use App\Entity\User;
use App\Enum\ConversationType;
use PHPUnit\Framework\TestCase;

class ConversationTest extends TestCase
{
    public function soloConversationDataProvider(): array
    {
        $members = array_map(fn()=> new User(), range(1, 2));

        return [[
            'name'    => 'Friend Conversation',
            'type'    => ConversationType::SOLO->toInt(),
            'members' => $members
        ]];
    }

    public function groupConversationDataProvider(): array
    {
        $members = array_map(fn() => new User(), range(1, 4));

        return [[
            'name'    => 'Group Conversation',
            'type'    => ConversationType::GROUP->toInt(),
            'members' => $members
        ]];
    }

    /**
     * @dataProvider soloConversationDataProvider
     */
    public function testSoloConversationData(string $name, int $type, array $members): void
    {
        $conversation = new Conversation();

        $conversation->setName($name);
        $conversation->setConversationType($type);

        foreach ($members as $member) {
            $conversation->addConversationMember($member);
        }

        $this->assertSame($members, $conversation->getConversationMembers()->toArray());
        $this->assertSame($name, $conversation->getName());
        $this->assertSame($type, $conversation->getConversationType());
    }

    /**
     * @dataProvider groupConversationDataProvider
     */
    public function testGroupConversationData(string $name, int $type, array $members): void
    {
        $conversation = new Conversation();

        $conversation->setName($name);
        $conversation->setConversationType($type);

        foreach ($members as $member) {
            $conversation->addConversationMember($member);
        }

        $this->assertSame($members, $conversation->getConversationMembers()->toArray());
        $this->assertSame($name, $conversation->getName());
        $this->assertSame($type, $conversation->getConversationType());
    }

    /**
     * @dataProvider groupConversationDataProvider
     */
    public function testCanAddAndRemoveConversarionMember(string $name, int $type, array $members): void
    {
        $conversation = new Conversation();
        $this->assertCount(0, $conversation->getConversationMembers());

        foreach ($members as $member) {
            $conversation->addConversationMember($member);
            $this->assertTrue($conversation->getConversationMembers()->contains($member));
        }
        $this->assertCount(4, $conversation->getConversationMembers());

        $conversation->removeConversationMember($members[0]);
        $this->assertCount(3, $conversation->getConversationMembers());
        $this->assertFalse($conversation->getConversationMembers()->contains($members[0]));
    }

    public function testCanAddAndRemoveMessages(): void
    {
        $conversation = new Conversation();
        $this->assertCount(0, $conversation->getMessages());

        $messages = array_map(fn() => new Message(), range(1, 4));
        foreach ($messages as $message) {
            $conversation->addMessage($message);
            $this->assertTrue($conversation->getMessages()->contains($message));
        }
        $this->assertCount(4, $conversation->getMessages());

        $conversation->removeMessage($messages[0]);
        $this->assertCount(3, $conversation->getMessages());
        $this->assertFalse($conversation->getMessages()->contains($messages[0]));
    }

    public function testTimestamps(): void
    {
        $conversation = new Conversation();

        $this->assertNull($conversation->getCreatedAt());
        $this->assertNull($conversation->getUpdatedAt());

        $conversation->setCreatedAt(new \DateTime());
        $conversation->setUpdatedAt(new \DateTime());

        $this->assertInstanceOf(\DateTimeInterface::class, $conversation->getCreatedAt());
        $this->assertInstanceOf(\DateTimeInterface::class, $conversation->getUpdatedAt());
    }
}