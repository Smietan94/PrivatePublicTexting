<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\NotificationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity(repositoryClass: NotificationRepository::class)]
#[ORM\Table(name: 'notifications')]
class Notification
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $notificationType = null;

    #[ORM\ManyToOne(inversedBy: 'sentNotifications')]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $sender = null;

    #[ORM\ManyToMany(targetEntity: User::class, inversedBy: 'receivedNotifications')]
    private Collection $receivers;

    #[ORM\Column]
    private ?bool $displayed = null;

    public function __construct()
    {
        $this->receivers = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNotificationType(): ?int
    {
        return $this->notificationType;
    }

    public function setNotificationType(int $notificationType): static
    {
        $this->notificationType = $notificationType;

        return $this;
    }

    public function getSender(): ?User
    {
        return $this->sender;
    }

    public function setSender(?User $sender): static
    {
        $this->sender = $sender;

        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getReceivers(): Collection
    {
        return $this->receivers;
    }

    public function addReceiver(User $receiver): static
    {
        if (!$this->receivers->contains($receiver)) {
            $this->receivers->add($receiver);
        }

        return $this;
    }

    public function removeReceiver(User $receiver): static
    {
        $this->receivers->removeElement($receiver);

        return $this;
    }

    public function isDisplayed(): ?bool
    {
        return $this->displayed;
    }

    public function setDisplayed(bool $displayed): static
    {
        $this->displayed = $displayed;

        return $this;
    }
}
