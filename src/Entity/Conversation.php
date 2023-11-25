<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ConversationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity(repositoryClass: ConversationRepository::class)]
#[ORM\Table(name: 'conversations')]
class Conversation
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $name = null;

    #[ORM\ManyToMany(targetEntity: User::class, inversedBy: 'conversations')]
    private Collection $conversationMembers;

    #[ORM\OneToMany(mappedBy: 'conversation', targetEntity: Message::class)]
    private Collection $messages;

    #[ORM\Column(nullable: false)]
    private int $conversationType;

    public function __construct()
    {
        $this->conversationMembers = new ArrayCollection();
        $this->messages = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): static
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getConversationMembers(): Collection
    {
        return $this->conversationMembers;
    }

    public function addConversationMember(User $conversationMember): static
    {
        if (!$this->conversationMembers->contains($conversationMember)) {
            $this->conversationMembers->add($conversationMember);
            $conversationMember->addConversation($this);
        }

        return $this;
    }

    public function removeConversationMember(User $conversationMember): static
    {
        $this->conversationMembers->removeElement($conversationMember);
        $conversationMember->removeConversation($this);

        return $this;
    }

    /**
     * @return Collection<int, Message>
     */
    public function getMessages(): Collection
    {
        return $this->messages;
    }

    public function addMessage(Message $message): static
    {
        if (!$this->messages->contains($message)) {
            $this->messages->add($message);
            $message->setConversation($this);
        }

        return $this;
    }

    public function removeMessage(Message $message): static
    {
        if ($this->messages->removeElement($message)) {
            // set the owning side to null (unless already changed)
            if ($message->getConversation() === $this) {
                $message->setConversation(null);
            }
        }

        return $this;
    }

    public function getConversationType(): int
    {
        return $this->conversationType;
    }

    public function setConversationType(int $conversationType): static
    {
        $this->conversationType = $conversationType;

        return $this;
    }
}
