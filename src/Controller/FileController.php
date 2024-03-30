<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Constants\Constant;
use App\Entity\Constants\RouteName;
use App\Entity\Constants\RoutePath;
use App\Entity\MessageAttachment;
use App\Entity\User;
use App\Repository\ConversationRepository;
use App\Repository\MessageAttachmentRepository;
use App\Repository\UserRepository;
use App\Service\FileService;
use League\Flysystem\FilesystemOperator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class FileController extends AbstractController
{
    private User               $currentUser;
    private FilesystemOperator $storage;

    public function __construct(
        FilesystemOperator                  $defaultStorage,
        private Security                    $security,
        private MessageAttachmentRepository $messageAttachmentRepository,
        private ConversationRepository      $conversationRepository,
        private UserRepository              $userRepository,
        private FileService                 $fileService,
    ) {
        $this->storage = $defaultStorage;
        // collecting current user
        $username          = $this->security->getUser()->getUserIdentifier();
        $this->currentUser = $this->userRepository->findOneBy(['username' => $username]);
    }

    /**
     * retrieves image in original quality
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
                'Content-Type' => $messageAttachment->getMimeType(),
            ]);
        }
    }

    /**
     * process img for conversation photo gallery
     *
     * @param  Request $request
     * @return Response
     */
    #[Route(
        RoutePath::PROCESS_GET_IMG_TAG,
        name: RouteName::APP_PROCESS_GET_IMG_TAG
    )]
    public function processGetImgTag(Request $request): Response
    {
        $jsonData = json_decode(
            $request->getContent(),
            true
        );

        $attachmentId = (int) $jsonData['attachmentId'];

        $filename = $this->messageAttachmentRepository->find($attachmentId)->getFileName();

        return $this->render('chat/chat_components/_carouselImg.html.twig', [
            'attachmentId' => $attachmentId,
            'filename'     => $filename
        ]);
    }

    /**
     * resizing image for thumbnails
     *
     * @param  mixed $attachmentId
     * @return Response
     */
    #[Route(
        RoutePath::GET_RESIZED_IMG,
        name: RouteName::APP_GET_RESIZED_IMG,
        requirements: ['attachmentId' => '[0-9]+']
    )]
    public function getResizedImg(int $attachmentId): Response
    {
        $messageAttachment = $this->messageAttachmentRepository->find($attachmentId);

        if (!$this->checkIfUserHaveAccesToFile($messageAttachment))
        {
            return new Response('You dont have access to this file', 403);
        }

        return new Response($this->fileService->resizeImg($messageAttachment)->__toString(), 200, [
            'Content-Type' => $messageAttachment->getMimeType(),
        ]);
    }

    /**
     * get attachment pager for conversation photo gallery
     *
     * @param  Request $request
     * @param  int     $conversationId
     * @return Response
     */
    #[Route(
        RoutePath::HANDLE_IMG_CAROUSEL,
        name: RouteName::APP_HANDLE_IMG_CAROUSEL,
        requirements: ['conversationId' => '[0-9]+']
    )]
    public function handleImgCarousel(Request $request, int $conversationId): Response
    {
        $jsonData = json_decode(
            $request->getContent(),
            true
        );

        $conversation = $this->conversationRepository->find($conversationId);
        $attachmentId = isset($jsonData['attachmentId']) ? (int) $jsonData['attachmentId']: null;

        // get page number where attachment is
        return $this->render('chat/chat_components/_imgCarouselModal.html.twig', [
            'attachmentId' => $attachmentId,
            'attachments'  => $this->fileService->getAttachmentsPager(
                (int) $request->query->get('page', 1),
                $conversation->getMessages(),
                $attachmentId
            )
        ]);
    }

    /**
     * checks if user have access to attachment
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