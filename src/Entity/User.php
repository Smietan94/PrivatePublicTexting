<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\OrderBy;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'users')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $password = null;

    #[Assert\Email(message: 'This value is not a valid email address')]
    #[ORM\Column(length: 50, unique: true)]
    private ?string $email = null;

    #[ORM\Column(length: 50, unique: true)]
    private ?string $username = null;

    #[ORM\Column(length: 50)]
    private ?string $name = null;

    #[ORM\Column(type: 'json')]
    private array $roles = [];

    #[ORM\OneToMany(mappedBy: 'requestingUser', targetEntity: FriendRequest::class, orphanRemoval: true)]
    private Collection $sentFriendRequests;

    #[ORM\OneToMany(mappedBy: 'requestedUser', targetEntity: FriendRequest::class, orphanRemoval: true)]
    private Collection $receivedFriendRequests;

    #[ORM\OneToMany(mappedBy: 'requestingUser', targetEntity: FriendHistory::class, orphanRemoval: true)]
    private Collection $sentFriendHistory;

    #[ORM\OneToMany(mappedBy: 'requestedUser', targetEntity: FriendHistory::class, orphanRemoval: true)]
    private Collection $receivedFriendHistory;

    #[ORM\ManyToMany(targetEntity: self::class)]
    #[OrderBy(['username' => 'ASC'])]
    private Collection $friends;

    #[ORM\ManyToMany(targetEntity: Conversation::class, mappedBy: 'conversationMembers')]
    private Collection $conversations;

    #[ORM\Column]
    private ?int $status = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTime $lastSeen = null;

    #[ORM\OneToMany(mappedBy: 'sender', targetEntity: Notification::class, orphanRemoval: true)]
    private Collection $sentNotifications;

    #[ORM\OneToMany(mappedBy: 'receiver', targetEntity: Notification::class, orphanRemoval: true)]
    #[OrderBy(['displayed' => 'ASC', 'createdAt' => 'DESC'])]
    private Collection $receivedNotifications;

    public function __construct()
    {
        $this->friends                = new ArrayCollection();
        $this->sentFriendRequests     = new ArrayCollection();
        $this->receivedFriendRequests = new ArrayCollection();
        $this->sentFriendHistory      = new ArrayCollection();
        $this->receivedFriendHistory  = new ArrayCollection();
        $this->conversations          = new ArrayCollection();
        $this->sentNotifications      = new ArrayCollection();
        $this->receivedNotifications  = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): static
    {
        $this->username = $username;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getUserIdentifier(): string
    {
        return $this->username;
    }

    public function getRoles(): array
    {
        $roles   = $this->roles;
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function eraseCredentials(): void
    {
    }

    /**
     * @return Collection<int, FriendRequest>
     */
    public function getSentFriendRequests(): Collection
    {
        return $this->sentFriendRequests;
    }

    public function addSentFriendRequest(FriendRequest $sentFriendRequest): static
    {
        // checks if sent request already in collection
        if (!$this->sentFriendRequests->contains($sentFriendRequest)) {
            $this->sentFriendRequests->add($sentFriendRequest);
            $sentFriendRequest->setRequestingUser($this);
        }

        return $this;
    }

    public function removeSentFriendRequest(FriendRequest $sentFriendRequest): static
    {
        if ($this->sentFriendRequests->removeElement($sentFriendRequest)) {
            // set the owning side to null (unless already changed)
            if ($sentFriendRequest->getRequestingUser() === $this) {
                $sentFriendRequest->setRequestingUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, FriendRequest>
     */
    public function getReceivedFriendRequests(): Collection
    {
        return $this->receivedFriendRequests;
    }

    public function addReceivedFriendRequest(FriendRequest $receivedFriendRequest): static
    {
        // checks if received request already in collection
        if (!$this->receivedFriendRequests->contains($receivedFriendRequest)) {
            $this->receivedFriendRequests->add($receivedFriendRequest);
            $receivedFriendRequest->setRequestedUser($this);
        }

        return $this;
    }

    public function removeReceivedFriendRequest(FriendRequest $receivedFriendRequest): static
    {
        if ($this->receivedFriendRequests->removeElement($receivedFriendRequest)) {
            // set the owning side to null (unless already changed)
            if ($receivedFriendRequest->getRequestedUser() === $this) {
                $receivedFriendRequest->setRequestedUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, FriendHistory>
     */
    public function getSentFriendHistory(): Collection
    {
        return $this->sentFriendHistory;
    }

    public function addSentFriendHistory(FriendHistory $sentFriendHistory): static
    {
        // checks if sent history already in collection
        if (!$this->sentFriendHistory->contains($sentFriendHistory)) {
            $this->sentFriendHistory->add($sentFriendHistory);
            $sentFriendHistory->setRequestingUser($this);
        }

        return $this;
    }

    public function removeSentFriendHistory(FriendHistory $sentFriendHistory): static
    {
        if ($this->sentFriendHistory->removeElement($sentFriendHistory)) {
            // set the owning side to null (unless already changed)
            if ($sentFriendHistory->getRequestingUser() === $this) {
                $sentFriendHistory->setRequestingUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, FriendHistory>
     */
    public function getReceivedFriendHistory(): Collection
    {
        return $this->receivedFriendHistory;
    }

    public function addReceivedFriendHistory(FriendHistory $receivedFriendHistory): static
    {
        // checks if received history already in collection
        if (!$this->receivedFriendHistory->contains($receivedFriendHistory)) {
            $this->receivedFriendHistory->add($receivedFriendHistory);
            $receivedFriendHistory->setRequestedUser($this);
        }

        return $this;
    }

    public function removeReceivedFriendHistory(FriendHistory $receivedFriendHistory): static
    {
        if ($this->receivedFriendHistory->removeElement($receivedFriendHistory)) {
            // set the owning side to null (unless already changed)
            if ($receivedFriendHistory->getRequestedUser() === $this) {
                $receivedFriendHistory->setRequestedUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, self>
     */
    public function getFriends(): Collection
    {
        return $this->friends;
    }

    public function addFriend(self $friend): static
    {
        if (!$this->friends->contains($friend)) {
            $this->friends->add($friend);
            $friend->addFriend($this);
        }

        return $this;
    }

    public function removeFriend(self $friend): static
    {
        $this->friends->removeElement($friend);
        if ($friend->getFriends()->contains($this)) {
            $friend->removeFriend($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, Conversation>
     */
    public function getConversations(): Collection
    {
        return $this->conversations;
    }

    public function addConversation(Conversation $conversation): static
    {
        if (!$this->conversations->contains($conversation)) {
            $this->conversations->add($conversation);
            $conversation->addConversationMember($this);
        }

        return $this;
    }

    public function removeConversation(Conversation $conversation): static
    {
        if ($this->conversations->removeElement($conversation)) {
            $conversation->removeConversationMember($this);
        }

        return $this;
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

    public function getLastSeen(): ?\DateTime
    {
        return $this->lastSeen;
    }

    public function setLastSeen(\DateTime $lastSeen): static
    {
        $this->lastSeen = $lastSeen;

        return $this;
    }

    public function __toString()
    {
        return $this->getUsername();
    }

    /**
     * @return Collection<int, Notification>
     */
    public function getSentNotifications(): Collection
    {
        return $this->sentNotifications;
    }

    public function addSentNotification(Notification $sentNotification): static
    {
        if (!$this->sentNotifications->contains($sentNotification)) {
            $this->sentNotifications->add($sentNotification);
            $sentNotification->setSender($this);
        }

        return $this;
    }

    public function removeSentNotification(Notification $sentNotification): static
    {
        if ($this->sentNotifications->removeElement($sentNotification)) {
            // set the owning side to null (unless already changed)
            if ($sentNotification->getSender() === $this) {
                $sentNotification->setSender(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Notification>
     */
    public function getReceivedNotifications(): Collection
    {
        return $this->receivedNotifications;
    }

    public function addReceivedNotification(Notification $receivedNotification): static
    {
        if (!$this->receivedNotifications->contains($receivedNotification)) {
            $this->receivedNotifications->add($receivedNotification);
            $receivedNotification->setReceiver($this);
        }

        return $this;
    }

    public function removeReceivedNotification(Notification $receivedNotification): static
    {
        if ($this->receivedNotifications->removeElement($receivedNotification)) {
            // set the owning side to null (unless already changed)
            if ($receivedNotification->getReceiver() === $this) {
                $receivedNotification->setReceiver(null);
            }
        }

        return $this;
    }
}
