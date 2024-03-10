<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\MessageRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\OrderBy;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity(repositoryClass: MessageRepository::class)]
#[ORM\Table(name: 'messages')]
class Message
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $senderId = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $message = null;

    #[ORM\ManyToOne(inversedBy: 'messages')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Conversation $conversation = null;

    #[ORM\Column()]
    private ?bool $attachment = null;

    #[ORM\OneToMany(mappedBy: 'message', targetEntity: MessageAttachment::class, orphanRemoval: true)]
    private Collection $messageAttachments;

    public function __construct()
    {
        $this->messageAttachments = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSenderId(): ?int
    {
        return $this->senderId;
    }

    public function setSenderId(int $senderId): static
    {
        $this->senderId = $senderId;

        return $this;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(string $message): static
    {
        $this->message = $message;

        return $this;
    }

    public function getConversation(): ?Conversation
    {
        return $this->conversation;
    }

    public function setConversation(?Conversation $conversation): static
    {
        $this->conversation = $conversation;

        return $this;
    }

    public function isAttachment(): ?bool
    {
        return $this->attachment;
    }

    public function setAttachment(?bool $attachment): static
    {
        $this->attachment = $attachment;

        return $this;
    }

    /**
     * @return Collection<int, MessageAttachment>
     */
    public function getMessageAttachments(): Collection
    {
        return $this->messageAttachments;
    }

    public function addMessageAttachment(MessageAttachment $messageAttachment): static
    {
        if (!$this->messageAttachments->contains($messageAttachment)) {
            $this->messageAttachments->add($messageAttachment);
            $messageAttachment->setMessage($this);
        }

        return $this;
    }

    public function removeMessageAttachment(MessageAttachment $messageAttachment): static
    {
        if ($this->messageAttachments->removeElement($messageAttachment)) {
            // set the owning side to null (unless already changed)
            if ($messageAttachment->getMessage() === $this) {
                $messageAttachment->setMessage(null);
            }
        }

        return $this;
    }
}
