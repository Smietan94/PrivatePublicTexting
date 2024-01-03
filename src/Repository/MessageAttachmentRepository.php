<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Message;
use App\Entity\MessageAttachment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * @extends ServiceEntityRepository<MessageAttachment>
 *
 * @method MessageAttachment|null find($id, $lockMode = null, $lockVersion = null)
 * @method MessageAttachment|null findOneBy(array $criteria, array $orderBy = null)
 * @method MessageAttachment[]    findAll()
 * @method MessageAttachment[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MessageAttachmentRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry                $registry,
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct($registry, MessageAttachment::class);
    }

    /**
     * storeAttachment
     *
     * @param  UploadedFile $file
     * @param  string       $path
     * @param  Message      $message
     * @return MessageAttachment
     */
    public function storeAttachment(UploadedFile $file, string $path, Message $message): MessageAttachment
    {
        $attachment  = new MessageAttachment();
        $pathExplode = explode('/', $path);
        $filename    = end($pathExplode);

        $attachment->setExtension($file->getClientOriginalExtension());
        $attachment->setFileName($filename);
        $attachment->setMimeType($file->getMimeType());
        $attachment->setPath($path);
        $attachment->setMessage($message);

        $this->entityManager->persist($attachment);
        $this->entityManager->flush();

        return $attachment;
    }

//    /**
//     * @return MessageAttachment[] Returns an array of MessageAttachment objects
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

//    public function findOneBySomeField($value): ?MessageAttachment
//    {
//        return $this->createQueryBuilder('m')
//            ->andWhere('m.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
