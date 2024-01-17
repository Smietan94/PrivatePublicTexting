<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Conversation;
use App\Entity\Message;
use App\Entity\User;
use App\Enum\ConversationType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
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
        ManagerRegistry                $registry,
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct($registry, Conversation::class);
    }

    /**
     * getFriendConversation
     *
     * @param  User $user
     * @param  User $friend
     * @return Conversation
     */
    public function getFriendConversation(User $user, User $friend): ?Conversation
    {
        $qb               = $this->entityManager->createQueryBuilder();
        $conversationType = ConversationType::SOLO->toInt();

        return $qb->select('c')
            ->from(Conversation::class, 'c')
            ->join('c.conversationMembers', 'user')
            ->join('c.conversationMembers', 'friend')
            ->andWhere($qb->expr()->andX(
                $qb->expr()->eq('user', ':user'),
                $qb->expr()->eq('friend', ':friend'),
                $qb->expr()->neq('user', 'friend'),
                $qb->expr()->eq('c.conversationType', ':conversationType')
            ))
            ->setParameters([
                'user'             => $user,
                'friend'           => $friend,
                'conversationType' => $conversationType
            ])
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * getGroupConversations
     *
     * @param  User $currentUser
     * @param  int  $conversationType
     * @return Conversation[] array
     */
    public function getConversations(User $currentUser, int $conversationType): array
    {
        $qb = $this->entityManager->createQueryBuilder();

        return $qb->select('c')
            ->from(Conversation::class, 'c')
            ->join('c.conversationMembers', 'user')
            ->leftJoin('c.lastMessage', 'lm')
            ->andWhere($qb->expr()->eq('user', ':user'))
            ->andWhere($qb->expr()->isMemberOf('c', 'user.conversations'))
            ->andWhere($qb->expr()->eq('c.conversationType', ':conversationType'))
            ->orderBy('CASE WHEN lm.createdAt IS NULL THEN 1 ELSE 0 END', 'ASC')
            ->addOrderBy('lm.createdAt', 'DESC')
            ->setParameters([
                'user' => $currentUser,
                'conversationType' => $conversationType
            ])
            ->getQuery()
            ->getResult();
    }


    /**
     * storeConversation
     *
     * @param  User     $user
     * @param  User[]   $conversationMembers
     * @param  int      $conversationType
     * @param  ?string  $conversationName
     * @return Conversation
     */
    public function storeConversation(User $user, array $conversationMembers, int $conversationType, ?string $conversationName = null): Conversation
    {
        $conversation = new Conversation();
        $conversation->setConversationType($conversationType);

        array_push($conversationMembers, $user);
        foreach($conversationMembers as $member) {
            $conversation->addConversationMember($member);
        }

        if (count($conversationMembers) > 2) {
            if ($conversationName != null) {
                $conversation->setName($conversationName);
            } else {
                $conversation->setName($this->createDefaultConversationName($conversationMembers));
            }
        }

        $this->entityManager->persist($conversation);
        $this->entityManager->flush();

        return $conversation;
    }
    
    /**
     * createDefaultConversationName
     *
     * @param  User[] $friends
     * @return string
     */
    public function createDefaultConversationName(array $friends): string
    {
        $friendUsernames = array_map(fn ($friend) => $friend->getUsername(), $friends);
        return implode(', ', $friendUsernames);
    }
    
    /**
     * getConversations
     *
     * @param  User   $currentUser
     * @param  string $searchTerm
     * @param  int    $conversationType
     * @return Conversation[] array
     */
    public function getSearchedConversations(User $currentUser, string $searchTerm, int $conversationType): ?array
    {
        // TODO collect conversations names like term or one of members names like but not currentuser
        $qb = $this->entityManager->createQueryBuilder();

        return $qb->select('c')->from(Conversation::class, 'c')
            ->join('c.conversationMembers', 'user')
            ->join('c.conversationMembers', 'cm')
            ->andWhere($qb->expr()->eq('c.conversationType', ':conversationType'))
            ->andWhere($qb->expr()->eq('user', ':user'))
            ->andWhere($qb->expr()->orX(
                $qb->expr()->like('LOWER(cm.username)', ':searchTerm'),
                $qb->expr()->like('LOWER(c.name)', ':searchTerm')
            ))
            ->setParameters([
                'conversationType' => $conversationType,
                'user'             => $currentUser,
                'searchTerm'       => '%' . strtolower($searchTerm) . '%'
            ])
            ->getQuery()
            ->getResult();
    }

    /**
     * addNewMember
     *
     * @param  int    $conversationId
     * @param  User[] $newMembers
     * @return array
     */
    public function addNewMember(int $conversationId, array $newMembers): array
    {
        $conversation = $this->find($conversationId);
        $messages     = [
            'success' => [],
            'warnig'  => []
        ];

        foreach ($newMembers as $user) {
            if (!in_array($conversation, $user->getConversations()->toArray())) {
                $conversation->addConversationMember($user);
                array_push($messages['success'], sprintf('%s successfully added to conversation', $user->getUsername()));
            } else {
                array_push($messages['warning'], sprintf('%s is not Your friend, cannot be added to conversation', $user->getUsername()));
            }
        }


        $this->entityManager->flush();

        return $messages;
    }

    public function updateLastMessage(int $conversationId, Message $message): void
    {
        $conversation = $this->find($conversationId);

        $conversation->setLastMessage($message);

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
