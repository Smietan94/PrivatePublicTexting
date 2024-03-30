<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Conversation;
use App\Entity\User;
use App\Enum\UserStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Security\User\UserLoaderInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @extends ServiceEntityRepository<User>
 *
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface, UserLoaderInterface
{
    public function __construct(
        ManagerRegistry                     $registry,
        private UserPasswordHasherInterface $passwordHasher,
        private EntityManagerInterface      $entityManager
    ) {
        parent::__construct($registry, User::class);
    }

    /**
     * updates user activity status
     *
     * @param  User $currentUser
     * @param  int  $status
     * @return void
     */
    public function changeActivityStatus(User $currentUser, int $status): void
    {
        $currentUser->setStatus($status);

        if (in_array($status, [UserStatus::INACTIVE->toInt(), UserStatus::LOGGEDOUT->toInt()])) {
            $currentUser->setLastSeen(new \DateTime());
        }

        $this->entityManager->flush();
    }

    /**
     * saves new user in database
     *
     * @param  array $data
     * @return User
     */
    public function store(array $data): User
    {
        $user = new User();

        $user->setEmail($data['email']);
        $user->setUsername($data['user_name']);
        $user->setName($data['name']);
        $user->setStatus(UserStatus::ACTIVE->toInt());
        $user->setLastSeen(new \DateTime());

        $this->upgradePassword(
            $user,
            $data['password']
        );

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    /**
     * updates password
     *
     * @param  User   $user
     * @param  string $newPassword
     * @return void
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newPassword): void
    {
        $newHashedPassword = $this->passwordHasher->hashPassword(
            $user,
            $newPassword
        );

        $user->setPassword($newHashedPassword);
    }

    /**
     * flushing user updates
     *
     * @param  mixed $currentUser
     * @return User
     */
    public function saveUpdates(User $currentUser): User
    {
        $this->entityManager->flush();
        return $currentUser;
    }

    /**
     * retrieves friends
     *
     * @param  User $user
     * @return User[] array
     */
    public function getFriendsArray(User $user): array
    {
        $qb = $this->getFriendsQuery($user);

        return $qb->getResult();
    }

    /**
     * checks if valid password provided
     *
     * @param  array $data
     * @return bool
     */
    public function checkPassword(array $data): bool
    {
        $user           = $this->findOneBy(['username' => $data['username']]);
        $hashedPassword = $this->passwordHasher->hashPassword(
            $user,
            $data['password']
        );

        return $hashedPassword === $user->getPassword();
    }

    /**
     * load user by identifier
     *
     * @param  string $identifier
     * @return UserInterface
     */
    public function loadUserByIdentifier(string $identifier): ?UserInterface
    {
        $user = $this->entityManager->createQuery(
            'SELECT u
            FROM App\Entity\User u
            WHERE u.username = :query
            OR u.email = :query'
        )
        ->setParameter('query', $identifier)
        ->getOneOrNullResult();

        return $user;
    }

    /**
     * retireves all friends
     *
     * @param  User $user
     * @return User[] array
     */
    public function getAllFriends(User $user): array
    {
        $friendsCollection = $user->getFriends();

        return $friendsCollection->toArray();
    }

    /**
     * search users by username or email
     *
     * @param  string $searchTerm
     * @param  string $username
     * @return User[] array
     */
    public function findUsers(?string $searchTerm, string $username): array
    {
        $query = $this->findUsersQueryBuilder($searchTerm, $username);

        return $query
            ->getQuery()
            ->getResult();
    }

    /**
     * creates user search query builder
     *
     * @param  string $searchTerm
     * @param  string $username
     * @return QueryBuilder
     */
    public function findUsersQueryBuilder(?string $searchTerm, string $username): QueryBuilder
    {
        $qB = $this->entityManager->createQueryBuilder();

        return $qB->select('partial u.{id, username}')
            ->from(User::class, 'u')
            ->andWhere(
                $qB->expr()->orX(
                    $qB->expr()->like('LOWER(u.username)', ':searchTerm'),
                    $qB->expr()->like('LOWER(u.email)', ':searchTerm')
            ))
            ->andWhere('u.username != :username')
            ->andWhere('u.status != :status')
            ->setParameters([
                'searchTerm' => '%' . strtolower($searchTerm) . '%',
                'username'   => $username,
                'status'     => UserStatus::DELETED->toInt()
            ]);
    }

    /**
     * retrieves all friends which ARE NOT members of conversation
     *
     * @param  int          $userId
     * @param  Conversation $conversation
     * @return User[] array
     */
    public function getNotConversationMemberFriends(int $userId, Conversation $conversation): array
    {
        $currentUser  = $this->find($userId);

        $qb = $this->entityManager->createQueryBuilder();

        return $qb->select('PARTIAL u.{id, username}')->from(User::class, 'u')
            ->andWhere(':user MEMBER OF u.friends')
            ->andWhere('u.status != :status')
            ->andWhere(':conversation NOT MEMBER OF u.conversations')
            ->setParameters([
                'user'         => $currentUser,
                'status'       => UserStatus::DELETED->toInt(),
                'conversation' => $conversation
            ])
            ->getQuery()
            ->getResult();
    }

//    /**
//     * @return User[] Returns an array of User objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('u')
//            ->andWhere('u.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('u.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?User
//    {
//        return $this->createQueryBuilder('u')
//            ->andWhere('u.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
