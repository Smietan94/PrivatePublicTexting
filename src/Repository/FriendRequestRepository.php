<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\FriendRequest;
use App\Entity\User;
use App\Enum\NotificationType;
use App\Service\NotificationService;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FriendRequest>
 *
 * @method FriendRequest|null find($id, $lockMode = null, $lockVersion = null)
 * @method FriendRequest|null findOneBy(array $criteria, array $orderBy = null)
 * @method FriendRequest[]    findAll()
 * @method FriendRequest[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FriendRequestRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry                $registry,
        private UserRepository         $userRepository,
        private EntityManagerInterface $entityManager,
        private NotificationService    $notificationService
    ) {
        parent::__construct($registry, FriendRequest::class);
    }

    /**
     * setNewFriendRequest
     *
     * @param  User $user
     * @param  User $requestedUser
     * @return FriendRequest
     */
    public function setNewFriendRequest(User $currentUser, User $requestedUser): FriendRequest
    {
        $friendRequest = new FriendRequest();

        $friendRequest->setRequestingUser($currentUser);
        $friendRequest->setRequestedUser($requestedUser);
        $friendRequest->setStatus(0);

        $this->entityManager->persist($friendRequest);
        $this->entityManager->flush();

        $this->notificationService->processFriendRequestReceive(
            $friendRequest,
            'receivedFriendRequestId'
        );

        $this->notificationService->processFriendStatusNotification(
            NotificationType::FRIEND_REQUEST_RECEIVED,
            $currentUser,
            $requestedUser
        );

        return $friendRequest;
    }

    /**
     * getFriendRequest
     *
     * @param  User $currentUser
     * @param  User $requestedUser
     * @param  int  $status
     * @return FriendRequest
     */
    public function getFriendRequest(User $currentUser, User $requestedUser, int $status): ?FriendRequest
    {
        $qb = $this->entityManager->createQueryBuilder();

        return $qb->select('fh')
            ->from(FriendRequest::class, 'fh')
            ->andWhere('fh.requestingUser = :currentUser')
            ->andWhere('fh.requestedUser  = :requestedUser')
            ->andWhere('fh.status         = :status')
            ->setParameters([
                'currentUser'   => $currentUser,
                'requestedUser' => $requestedUser,
                'status'        => $status
            ])
            ->getQuery()
            ->getOneOrNullResult();
    }

//    /**
//     * @return FriendRequest[] Returns an array of FriendRequest objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('f')
//            ->andWhere('f.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('f.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?FriendRequest
//    {
//        return $this->createQueryBuilder('f')
//            ->andWhere('f.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
