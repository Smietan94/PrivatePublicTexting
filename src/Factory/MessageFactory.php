<?php

namespace App\Factory;

use App\Entity\Message;
use App\Repository\ConversationRepository;
use App\Repository\MessageRepository;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\RepositoryProxy;

/**
 * @extends ModelFactory<Message>
 *
 * @method        Message|Proxy                     create(array|callable $attributes = [])
 * @method static Message|Proxy                     createOne(array $attributes = [])
 * @method static Message|Proxy                     find(object|array|mixed $criteria)
 * @method static Message|Proxy                     findOrCreate(array $attributes)
 * @method static Message|Proxy                     first(string $sortedField = 'id')
 * @method static Message|Proxy                     last(string $sortedField = 'id')
 * @method static Message|Proxy                     random(array $attributes = [])
 * @method static Message|Proxy                     randomOrCreate(array $attributes = [])
 * @method static MessageRepository|RepositoryProxy repository()
 * @method static Message[]|Proxy[]                 all()
 * @method static Message[]|Proxy[]                 createMany(int $number, array|callable $attributes = [])
 * @method static Message[]|Proxy[]                 createSequence(iterable|callable $sequence)
 * @method static Message[]|Proxy[]                 findBy(array $attributes)
 * @method static Message[]|Proxy[]                 randomRange(int $min, int $max, array $attributes = [])
 * @method static Message[]|Proxy[]                 randomSet(int $number, array $attributes = [])
 */
final class MessageFactory extends ModelFactory
{
    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#factories-as-services
     *
     * @todo inject services if required
     */
    public function __construct(
        private ConversationRepository $conversationRepository
    ) {
        parent::__construct();
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     *
     * @todo add your default values here
     */
    protected function getDefaults(): array
    {
        $conversation = $this->conversationRepository->find(6);
        $membersIds   = array_map(fn($member) => $member->getId(), $conversation->getConversationMembers()->toArray());
        $date         = self::faker()->dateTimeBetween('-2 months', 'now');
        return [
            'conversation' => $conversation,
            'createdAt' => $date,
            'message' => self::faker()->words(rand(1, 20), true),
            'senderId' => self::faker()->randomElement($membersIds),
            'updatedAt' => $date,
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    protected function initialize(): self
    {
        return $this
            // ->afterInstantiate(function(Message $message): void {})
        ;
    }

    protected static function getClass(): string
    {
        return Message::class;
    }
}
