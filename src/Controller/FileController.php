<?php

namespace App\Controller;

use App\Entity\Constants\RouteName;
use App\Entity\Constants\RoutePath;
use App\Entity\MessageAttachment;
use App\Entity\User;
use App\Repository\MessageAttachmentRepository;
use App\Repository\UserRepository;
use League\Flysystem\FilesystemOperator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class FileController extends AbstractController
{
    private User $currentUser;
    private FilesystemOperator $storage;

    public function __construct(
        FilesystemOperator                  $defaultStorage,
        private MessageAttachmentRepository $messageAttachmentRepository,
        private Security                    $security,
        private UserRepository              $userRepository
    ) {
        $this->storage = $defaultStorage;
        // collecting current user
        $username          = $this->security->getUser()->getUserIdentifier();
        $this->currentUser = $this->userRepository->findOneBy(['username' => $username]);
    }

    /**
     * getImage
     *
     * @param  int $attachmentId
     * @return Response
     */
    #[Route(
        RoutePath::GET_IMG,
        name: RouteName::APP_GET_SENT_IMG,
        requirements: ['attachmentId' => '[0-9]+']
    )]
    public function getImage(int $attachmentId): Response
    {
        $messageAttachment = $this->messageAttachmentRepository->find($attachmentId);
        $filePath          = $messageAttachment->getPath();

        if (!$this->checkIfUserHaveAccesToFile($messageAttachment))
        {
            return new Response('You dont have access to this file', 403);
        }

        if ($this->storage->has($filePath)) {
            $fileContents = $this->storage->read($filePath);

            return new Response($fileContents, 200, [
                'Content-Type' => 'image/png',
            ]);
        }
    }

    /**
     * checkIfUserHaveAccesToFile
     *
     * @param  MessageAttachment $messageAttachment
     * @return bool
     */
    private function checkIfUserHaveAccesToFile(MessageAttachment $messageAttachment): bool
    {
        $conversation = $messageAttachment->getMessage()->getConversation();

        return $conversation->getConversationMembers()->contains($this->currentUser);
    }
}