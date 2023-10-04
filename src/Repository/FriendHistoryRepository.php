<?php

namespace App\Repository;

use App\Entity\FriendHistory;
use App\Entity\User;
use App\Enum\FriendStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FriendHistory>
 *
 * @method FriendHistory|null find($id, $lockMode = null, $lockVersion = null)
 * @method FriendHistory|null findOneBy(array $criteria, array $orderBy = null)
 * @method FriendHistory[]    findAll()
 * @method FriendHistory[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FriendHistoryRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager,
    ) {
        parent::__construct($registry, FriendRequestHistory::class);
    }

    public function getFriendHistory(User $currentUser, User $friend): ?FriendHistory
    {
        $qb = $this->entityManager->createQueryBuilder();

        return $qb->select('fh')
            ->from(FriendHistory::class, 'fh')
            ->innerJoin(User::class, 'u1', Join::WITH,
                $qb->expr()->orX(
                    $qb->expr()->eq('fh.requestingUser', 'u1'),
                    $qb->expr()->eq('fh.requestedUser', 'u1')
                ))
            ->innerJoin(User::class, 'u2', Join::WITH,
                $qb->expr()->orX(
                    $qb->expr()->eq('fh.requestingUser', 'u2'),
                    $qb->expr()->eq('fh.requestedUser', 'u2')
                ))
            ->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->andX(
                        $qb->expr()->eq('u1', ':currentUser'),
                        $qb->expr()->eq('u2', ':friend')
                    ),
                    $qb->expr()->andX(
                        $qb->expr()->eq('u1', ':friend'),
                        $qb->expr()->eq('u2', ':currentUser')
                    )
                ))
            ->andWhere('fh.status = :accepted')
            ->setParameters([
                'currentUser' => $currentUser,
                'friend'      => $friend,
                'accepted'    => FriendStatus::ACCEPTED->value
            ])
            ->getQuery()
            ->getOneOrNullResult();
    }
}
