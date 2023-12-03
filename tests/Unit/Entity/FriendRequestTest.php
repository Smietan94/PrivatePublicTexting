<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\FriendRequest;
use App\Entity\User;
use App\Enum\FriendStatus;
use PHPUnit\Framework\TestCase;

class FriendRequestTest extends TestCase
{
    public function friendStatusProvider()
    {
        yield [FriendStatus::PENDING->toInt()];
        yield [FriendStatus::ACCEPTED->toInt()];
        yield [FriendStatus::REJECTED->toInt()];
        yield [FriendStatus::CANCELLED->toInt()];
        yield [FriendStatus::EXPIRED->toInt()];
        yield [FriendStatus::DELETED->toInt()];
    }

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

    /**
     * @dataProvider friendStatusProvider
     */
    public function testSetAndGetStatus(int $status): void
    {
        $friendRequest = new FriendRequest();

        $this->assertNull($friendRequest->getStatus());

        $friendRequest->setStatus($status);
        $this->assertEquals($status, $friendRequest->getStatus());
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