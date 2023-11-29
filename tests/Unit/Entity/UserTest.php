<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\Conversation;
use App\Entity\FriendHistory;
use App\Entity\FriendRequest;
use App\Entity\User;
use App\Enum\UserStatus;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    public function testSetAndGetPassword(): void
    {
        $password = 'haslomaslo1';
        $user     = new User();
        $this->assertNull($user->getPassword());

        $user->setPassword($password);
        $this->assertSame($password, $user->getPassword());
    }

    public function testSetAndGetEmail(): void
    {
        $email = 'kluskaKot2137@example.com';
        $user  = new User();
        $this->assertNull($user->getEmail());

        $user->setEmail($email);
        $this->assertSame($email, $user->getEmail());
    }

    public function testSetAndGetUsername(): void
    {
        $username = 'klusia_kot';
        $user     = new User();
        $this->assertNull($user->getUsername());

        $user->setUsername($username);
        $this->assertSame($username, $user->getUsername());
    }

    public function testSetAndGetName(): void
    {
        $name = 'Kluska Kot';
        $user = new User();
        $this->assertNull($user->getName());

        $user->setName($name);
        $this->assertSame($name, $user->getName());
    }

    public function testSetAndGetUserStatus(): void
    {
        $status = UserStatus::ACTIVE->toInt();
        $user   = new User();
        $this->assertNull($user->getStatus());

        $user->setStatus($status);
        $this->assertSame($status, $user->getStatus());
        $this->assertNotSame(UserStatus::LOGGEDOUT->toInt(), $user->getStatus());
    }

    public function testGetRoles(): void
    {
        $user = new User();
        $this->assertNotEmpty($user->getRoles());
        $this->assertSame(['ROLE_USER'], $user->getRoles());
    }

    public function testSetAndGetSentFriendRequests(): void
    {
        $user = new User();
        $this->assertEmpty($user->getSentFriendRequests());

        $sentFriendRequests = array_map(fn() => new FriendRequest(), range(1, 4));
        foreach ($sentFriendRequests as $sentFriendRequest) {
            $user->addSentFriendRequest($sentFriendRequest);
            $this->assertTrue($user->getSentFriendRequests()->contains($sentFriendRequest));
            $this->assertSame($user, $sentFriendRequest->getRequestingUser());
            $this->assertNotSame($user, $sentFriendRequest->getRequestedUser());
        }
        $this->assertNotEmpty($user->getSentFriendRequests());

        $user->removeSentFriendRequest($sentFriendRequests[0]);
        $this->assertFalse($user->getSentFriendRequests()->contains($sentFriendRequests[0]));
        $this->assertNull($sentFriendRequests[0]->getRequestingUser());
    }

    public function testSetAndGetReceivedFriendRequests(): void
    {
        $user = new User();
        $this->assertEmpty($user->getReceivedFriendRequests());

        $receivedFriendRequests = array_map(fn() => new FriendRequest(), range(1, 4));
        foreach ($receivedFriendRequests as $receivedFriendRequest) {
            $user->addReceivedFriendRequest($receivedFriendRequest);
            $this->assertTrue($user->getReceivedFriendRequests()->contains($receivedFriendRequest));
            $this->assertSame($user, $receivedFriendRequest->getRequestedUser());
            $this->assertNotSame($user, $receivedFriendRequest->getRequestingUser());
        }
        $this->assertNotEmpty($user->getReceivedFriendRequests());

        $user->removeReceivedFriendRequest($receivedFriendRequests[0]);
        $this->assertFalse($user->getreceivedFriendRequests()->contains($receivedFriendRequests[0]));
        $this->assertNull($receivedFriendRequests[0]->getRequestingUser());
    }

    public function testSetAndGetSentFriendHistory(): void
    {
        $user = new User();
        $this->assertEmpty($user->getSentFriendHistory());

        $sentFriendHistory = array_map(fn() => new FriendHistory(), range(1, 4));
        foreach ($sentFriendHistory as $_sentFriendHistory) {
            $user->addSentFriendHistory($_sentFriendHistory);
            $this->assertTrue($user->getSentFriendHistory()->contains($_sentFriendHistory));
            $this->assertSame($user, $_sentFriendHistory->getRequestingUser());
            $this->assertNotSame($user, $_sentFriendHistory->getRequestedUser());
        }
        $this->assertNotEmpty($user->getSentFriendHistory());

        $user->removeSentFriendHistory($sentFriendHistory[0]);
        $this->assertFalse($user->getSentFriendHistory()->contains($sentFriendHistory[0]));
        $this->assertNull($sentFriendHistory[0]->getRequestingUser());
    }

    public function testSetAndGetReceivedFriendHistory(): void
    {
        $user = new User();
        $this->assertEmpty($user->getReceivedFriendHistory());

        $receivedFriendHistory = array_map(fn() => new FriendHistory(), range(1, 4));
        foreach ($receivedFriendHistory as $_receivedFriendHistory) {
            $user->addReceivedFriendHistory($_receivedFriendHistory);
            $this->assertTrue($user->getReceivedFriendHistory()->contains($_receivedFriendHistory));
            $this->assertSame($user, $_receivedFriendHistory->getRequestedUser());
            $this->assertNotSame($user, $_receivedFriendHistory->getRequestingUser());
        }
        $this->assertNotEmpty($user->getReceivedFriendHistory());

        $user->removeReceivedFriendHistory($receivedFriendHistory[0]);
        $this->assertFalse($user->getReceivedFriendHistory()->contains($receivedFriendHistory[0]));
        $this->assertNull($receivedFriendHistory[0]->getRequestingUser());
    }

    public function testAddAndRemoveFriends(): void
    {
        $user = new User();
        $this->assertEmpty($user->getFriends());

        $friends = array_map(fn() => new User(), range(1, 4));
        foreach ($friends as $friend) {
            $user->addFriend($friend);
            $this->assertTrue($user->getFriends()->contains($friend));
            $this->assertTrue($friend->getFriends()->contains($user));
        }
        $this->assertNotEmpty($user->getFriends());

        $user->removeFriend($friends[0]);
        $this->assertFalse($user->getFriends()->contains($friends[0]));
        $this->assertFalse($friends[0]->getFriends()->contains($user));
    }

    public function testStartAndLeeaveConversations(): void
    {
        $user = new User();
        $this->assertEmpty($user->getConversations());

        $conversations = array_map(fn() => new Conversation(), range(1, 4));
        foreach ($conversations as $conversation) {
            $user->addConversation($conversation);
            $this->assertTrue($user->getConversations()->contains($conversation));
            $this->assertTrue($conversation->getConversationMembers()->contains($user));
        }
        $this->assertNotEmpty($user->getConversations());

        $user->removeConversation($conversations[0]);
        $this->assertFalse($user->getConversations()->contains($conversations[0]));
        $this->assertFalse($conversations[0]->getConversationMembers()->contains($user));
    }

    public function testTimestamps(): void
    {
        $user = new User();

        $this->assertNull($user->getCreatedAt());
        $this->assertNull($user->getUpdatedAt());
        $this->assertNull($user->getLastSeen());

        $user->setCreatedAt(new \DateTime());
        $user->setUpdatedAt(new \DateTime());
        $user->setLastSeen(new \DateTime());

        $this->assertInstanceOf(\DateTimeInterface::class, $user->getCreatedAt());
        $this->assertInstanceOf(\DateTimeInterface::class, $user->getUpdatedAt());
        $this->assertInstanceOf(\DateTimeInterface::class, $user->getLastSeen());
    }
}