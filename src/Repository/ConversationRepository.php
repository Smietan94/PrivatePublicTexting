<?php

namespace App\Repository;

use App\Entity\Conversation;
use App\Entity\Message;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Conversation>
 *
 * @method Conversation|null find($id, $lockMode = null, $lockVersion = null)
 * @method Conversation|null findOneBy(array $criteria, array $orderBy = null)
 * @method Conversation[]    findAll()
 * @method Conversation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ConversationRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct($registry, Conversation::class);
    }

    public function getFriendConversation(User $user, User $friend): ?Conversation
    {
        $qb = $this->entityManager->createQueryBuilder();

        return $qb->select('c')
            ->from(Conversation::class, 'c')
            ->join('c.conversationMembers', 'user')
            ->join('c.conversationMembers', 'friend')
            ->join('user.conversations', 'conversations')
            ->andWhere($qb->expr()->andX(
                $qb->expr()->eq('user', ':user'),
                $qb->expr()->eq('friend', ':friend')
            ))
            ->andWhere(
                $qb->expr()->neq('user', 'friend')
            )
            ->setParameter('user', $user)
            ->setParameter('friend', $friend)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function storeConversation(User $user, User $friend): void
    {
        $conversation = new Conversation();

        $conversation->addConversationMember($user);
        $conversation->addConversationMember($friend);

        $this->entityManager->persist($conversation);
        $this->entityManager->flush();
    }

//    /**
//     * @return Conversation[] Returns an array of Conversation objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('c.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Conversation
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
