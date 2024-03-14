<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\FriendHistory;
use App\Entity\User;
use App\Enum\FriendStatus;
use PHPUnit\Framework\TestCase;

class FriendHistoryTest extends TestCase
{
    public function friendStatusProvider()
    {
        foreach (FriendStatus::cases() as $friendStatus) {
            yield [$friendStatus->toInt()];
        }
    }

    public function testSetAndGetRequestingAndRequestedUser(): void
    {
        $friendHisotry = new FriendHistory();

        $this->assertNull($friendHisotry->getRequestedUser());
        $this->assertNull($friendHisotry->getRequestingUser());

        $requestedUser  = new User();
        $requestingUser = new User();

        $friendHisotry->setRequestedUser($requestedUser);
        $friendHisotry->setRequestingUser($requestingUser);

        $this->assertSame($requestedUser, $friendHisotry->getRequestedUser());
        $this->assertSame($requestingUser, $friendHisotry->getRequestingUser());
    }

    /**
     * @dataProvider friendStatusProvider
     */
    public function testSetAndGetStatus(int $status): void
    {
        $friendHistory = new FriendHistory();

        $this->assertNull($friendHistory->getStatus());

        $friendHistory->setStatus($status);
        $this->assertSame($status, $friendHistory->getStatus());
    }

    public function testTimestamps(): void
    {
        $friendHistory = new FriendHistory();

        $this->assertNull($friendHistory->getCreatedAt());
        $this->assertNull($friendHistory->getUpdatedAt());
        $this->assertNull($friendHistory->getSentAt());

        $friendHistory->setCreatedAt(new \DateTime());
        $friendHistory->setUpdatedAt(new \DateTime());
        $friendHistory->setSentAt(new \DateTime());

        $this->assertInstanceOf(\DateTimeInterface::class, $friendHistory->getCreatedAt());
        $this->assertInstanceOf(\DateTimeInterface::class, $friendHistory->getUpdatedAt());
        $this->assertInstanceOf(\DateTimeInterface::class, $friendHistory->getSentAt());
    }
}