<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Constants\Constant;
use App\Entity\Message;
use App\Entity\MessageAttachment;
use App\Repository\MessageAttachmentRepository;
use League\Flysystem\FilesystemOperator;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class MessageAttachmentService
{
    public function __construct(
        private FilesystemOperator          $defaultStorage,
        private MessageAttachmentRepository $messageAttachmentRepository,
    ) {
    }

    /**
     * processAttachmentUpload
     *
     * @param  UploadedFile[] $files
     * @param  int            $senderId
     * @return string[]
     */
    public function processAttachmentUpload(array $files, int $senderId, int $conversationId): array
    {
        $filePaths = [];
        $basePath  = sprintf(Constant::CHAT_FILES_STORAGE_PATH, $conversationId);

        foreach ($files as $file) {
            $pathFormat = match ($file->getClientMimeType()) {
                'image/jpeg'      => $basePath . 'images/%s',
                'image/png'       => $basePath . 'images/%s',
                // 'text/plain'      => $basePath . 'text_files/%s',
                // 'application/pdf' => $basePath . 'pdfs/%s'
            };
            $fileName = $this->generateAttachmentName($senderId, $file->getClientOriginalExtension());
            $path     = sprintf($pathFormat, $fileName);
            array_push($filePaths, $path);

            $this->defaultStorage->write($path, $file->getContent());
        }

        return $filePaths;
    }

    /**
     * generateAttachmentName
     *
     * @param  int    $senderId
     * @param  string $extension
     * @return string
     */
    public function generateAttachmentName(int $senderId, string $extension): string
    {
        $date            = (new \DateTime())->format('dmYHisu');
        $randomNumber    = mt_rand(0, 99999);
        $formattedNumber = sprintf('%05d', $randomNumber);

        return sprintf('%d_%s_%s.%s', $senderId, $date, $formattedNumber, $extension);
    }

    /**
     * processAttachmentsStore
     *
     * @param  UploadedFile[] $files
     * @param  string[]       $paths
     * @param  Message        $message
     * @return MessageAttachment[]
     */
    public function processAttachmentsDataStore(array $files, array $paths, Message $message): array
    {
        $attachments = [];
        foreach ($files as $key => $file) {
            array_push(
                $attachments,
                $this->messageAttachmentRepository->storeAttachment($file, $paths[$key], $message)
            );
        }

        return $attachments;
    }
}