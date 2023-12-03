<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Conversation;
use App\Entity\Message;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Message>
 *
 * @method Message|null find($id, $lockMode = null, $lockVersion = null)
 * @method Message|null findOneBy(array $criteria, array $orderBy = null)
 * @method Message[]    findAll()
 * @method Message[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MessageRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct($registry, Message::class);
    }

    /**
     * getMessageQuery
     *
     * @param  Conversation $conversation
     * @param  int $conversationType
     * @return Query
     */
    public function getMessageQuery(Conversation $conversation, int $conversationType): Query
    {
        $qb = $this->entityManager->createQueryBuilder();

        $qb = $qb->select('m')
            ->from(Message::class, 'm')
            ->join('m.conversation', 'c')
            ->andWhere($qb->expr()->eq('c', ':conversation'))
            ->setParameter('conversation', $conversation);

        foreach ($conversation->getConversationMembers()->toArray() as $loopIndex => $conversationMember) {
            $qb = $qb->andWhere($qb->expr()->isMemberOf(":conversationMember{$loopIndex}", 'c.conversationMembers'))
                ->setParameter("conversationMember{$loopIndex}", $conversationMember);
        }

        $qb->andWhere($qb->expr()->eq('c.conversationType', ':conversationType'))
            ->orderBy('m.createdAt', 'DESC')
            ->setParameter('conversationType', $conversationType);

        return $qb->getQuery();
    }

    /**
     * storeMessage
     *
     * @param  Conversation $conversation
     * @param  int $senderId
     * @param  string $messageText
     * @return void
     */
    public function storeMessage(Conversation $conversation, int $senderId, string $messageText): void
    {
        $message = new Message();

        $message->setConversation($conversation);
        $message->setSenderId($senderId);
        $message->setMessage($messageText);

        $this->entityManager->persist($message);
        $this->entityManager->flush();
    }

//    /**
//     * @return Message[] Returns an array of Message objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('m')
//            ->andWhere('m.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('m.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Message
//    {
//        return $this->createQueryBuilder('m')
//            ->andWhere('m.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
