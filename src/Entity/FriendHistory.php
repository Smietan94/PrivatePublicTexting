<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\FriendHistoryRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity(repositoryClass: FriendHistoryRepository::class)]
#[ORM\Table(name: 'friend_history')]
class FriendHistory
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $status = null;

    #[ORM\Column]
    private ?\DateTime $sentAt = null;

    #[ORM\ManyToOne(inversedBy: 'sentFriendRequestsHistory')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $requestingUser = null;

    #[ORM\ManyToOne(inversedBy: 'receivedFriendRequestsHistory')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $requestedUser = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStatus(): ?int
    {
        return $this->status;
    }

    public function setStatus(int $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getSentAt(): \DateTime
    {
        return $this->sentAt;
    }

    public function setSentAt(\DateTime $sentAt): static
    {
        $this->sentAt = $sentAt;

        return $this;
    }

    public function getRequestingUser(): ?User
    {
        return $this->requestingUser;
    }

    public function setRequestingUser(?User $requestingUser): static
    {
        $this->requestingUser = $requestingUser;

        return $this;
    }

    public function getRequestedUser(): ?User
    {
        return $this->requestedUser;
    }

    public function setRequestedUser(?User $requestedUser): static
    {
        $this->requestedUser = $requestedUser;

        return $this;
    }
}
