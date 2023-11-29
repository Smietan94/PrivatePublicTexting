<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\FriendRequest;
use App\Entity\User;
use App\Enum\FriendStatus;
use PHPUnit\Framework\TestCase;

class FriendRequestTest extends TestCase
{
    public function testSetAndGetRequestingAndRequestedUser(): void
    {
        $friendRequest = new FriendRequest();

        $this->assertNull($friendRequest->getRequestedUser());
        $this->assertNull($friendRequest->getRequestingUser());

        $requestedUser  = new User();
        $requestingUser = new User();

        $friendRequest->setRequestedUser($requestedUser);
        $friendRequest->setRequestingUser($requestingUser);

        $this->assertSame($requestedUser, $friendRequest->getRequestedUser());
        $this->assertSame($requestingUser, $friendRequest->getRequestingUser());
    }

    public function testSetAndGetStatus(): void
    {
        $friendRequest = new FriendRequest();

        $this->assertNull($friendRequest->getStatus());

        $friendRequest->setStatus(FriendStatus::PENDING->toInt());
        $this->assertEquals(FriendStatus::PENDING->toInt(), $friendRequest->getStatus());
    }

    public function testTimestamps(): void
    {
        $friendRequest = new FriendRequest();

        $this->assertNull($friendRequest->getCreatedAt());
        $this->assertNull($friendRequest->getUpdatedAt());

        $friendRequest->setCreatedAt(new \DateTime());
        $friendRequest->setUpdatedAt(new \DateTime());

        $this->assertInstanceOf(\DateTimeInterface::class, $friendRequest->getCreatedAt());
        $this->assertInstanceOf(\DateTimeInterface::class, $friendRequest->getUpdatedAt());
    }
}