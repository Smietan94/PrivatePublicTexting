<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Notification;
use App\Entity\User;
use App\Enum\NotificationStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Notification>
 *
 * @method Notification|null find($id, $lockMode = null, $lockVersion = null)
 * @method Notification|null findOneBy(array $criteria, array $orderBy = null)
 * @method Notification[]    findAll()
 * @method Notification[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class NotificationRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct($registry, Notification::class);
    }

    /**
     * save notification in db
     *
     * @param  int    $notificationType
     * @param  User   $sender
     * @param  User   $receiver
     * @param  string $message
     * @return Notification
     */
    public function storeNotification(int $notificationType, User $sender, User $receiver, string $message, ?int $conversationId = null): Notification
    {
        $notification = new Notification();

        $notification->setNotificationType($notificationType);
        $notification->setDisplayed(NotificationStatus::UNSEEN->toBool());
        $notification->setSender($sender);
        $notification->setReceiver($receiver);
        $notification->setMessage($message);
        $notification->setUpdatedAt(new \DateTime());
        $notification->setConversationId($conversationId);

        $this->entityManager->persist($notification);
        $this->entityManager->flush();

        return $notification;
    }

    /**
     * retrievs all unseen notifications
     *
     * @param  User $user
     * @return Notification[]
     */
    public function getUnseenNotifications(User $user): array
    {
        $qb = $this->entityManager->createQueryBuilder();

        return $qb->select('n')
            ->from(Notification::class, 'n')
            ->andWhere(
                $qb->expr()->eq('n.receiver', ':user'),
                $qb->expr()->eq('n.displayed', ':displayed')
            )
            ->setParameters([
                'user'      => $user,
                'displayed' => NotificationStatus::UNSEEN->toBool()
            ])
            ->getQuery()
            ->getResult();
    }

    /**
     * sets notification display status
     *
     * @param  int $notificationId
     * @return Notification
     */
    public function setNotificationDisplayStatus(int $notificationId): Notification
    {
        $notification = $this->find($notificationId);

        $notification->setDisplayed(NotificationStatus::DISPLAYED->toBool());
        $notification->setUpdatedAt(new \DateTime());

        $this->entityManager->flush();

        return $notification;
    }

//    /**
//     * @return Notification[] Returns an array of Notification objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('n')
//            ->andWhere('n.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('n.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Notification
//    {
//        return $this->createQueryBuilder('n')
//            ->andWhere('n.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
