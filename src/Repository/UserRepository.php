<?php

namespace App\Repository;

use App\Entity\User;
use App\Enum\UserSatatus;
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
        ManagerRegistry $registry,
        private UserPasswordHasherInterface $passwordHasher,
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct($registry, User::class);
    }

    public function store(array $data): User
    {
        $user           = new User();
        $hashedPassword = $this->passwordHasher->hashPassword(
            $user,
            $data['password']
        );

        $user->setEmail($data['email']);
        $user->setUsername($data['user_name']);
        $user->setName($data['name']);
        $user->setPassword($hashedPassword);
        $user->setStatus(UserSatatus::ACTIVE->toInt());
        $user->setLastSeen(new \DateTime());

        $this->upgradePassword($user, $hashedPassword);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    public function getFriendsArray(User $user): array
    {
        $qb = $this->getFriendsQuery($user);

        return $qb->getQuery()->getResult();
    }

    public function getFriendsQuery(User $user): QueryBuilder
    {
        return $this->entityManager->createQueryBuilder()
            ->select('u')
            ->from(User::class, 'u')
            ->leftJoin('u.friends', 'f')
            ->andWhere(':user MEMBER OF u.friends')
            ->orderBy('u.username', 'ASC')
            ->setParameter('user', $user);
    }

    public function checkPassword(array $data): bool
    {
        $user = $this->findOneBy(['username' => $data['username']]);
        $hashedPassword = $this->passwordHasher->hashPassword(
            $user,
            $data['password']
        );
        return $hashedPassword === $user->getPassword();
    }

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

    public function getAllFriends(User $user): array
    {
        $friendsCollection = $user->getFriends();

        return $friendsCollection->toArray();
    }

    public function findUsers(?string $searchTerm, string $username): array
    {
        $query = $this->findUsersQueryBuilder($searchTerm, $username);

        return $query
            ->getQuery()
            ->getResult();
    }

    public function findUsersQueryBuilder(?string $searchTerm, string $username): QueryBuilder
    {
        $qB = $this->entityManager->createQueryBuilder();

        return $qB->select('u')
            ->from(User::class, 'u')
            ->andWhere(
                $qB->expr()->orX(
                    $qB->expr()->like('LOWER(u.username)', ':searchTerm'),
                    $qB->expr()->like('LOWER(u.email)', ':searchTerm')
            ))
            ->andWhere('u.username != :username')
            ->setParameters([
                'searchTerm' => '%' . strtolower($searchTerm) . '%',
                'username'   => $username
            ]);
    }

    public function changeStatus(int $status, User $user): void
    {
        $user->setStatus($status);
        $this->entityManager->flush();
    }

    public function updateLastSeen(User $user): void
    {
        $user->setLastSeen(new \DateTime());
        $this->entityManager->flush();
    }

    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
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
