<?php

namespace App\Repository;

use App\Entity\FriendRequestHistory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FriendRequestHistory>
 *
 * @method FriendRequestHistory|null find($id, $lockMode = null, $lockVersion = null)
 * @method FriendRequestHistory|null findOneBy(array $criteria, array $orderBy = null)
 * @method FriendRequestHistory[]    findAll()
 * @method FriendRequestHistory[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FriendRequestHistoryRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager,
    ) {
        parent::__construct($registry, FriendRequestHistory::class);
    }

}
