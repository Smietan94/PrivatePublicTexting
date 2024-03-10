<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\Message;
use App\Entity\MessageAttachment;
use App\Repository\MessageAttachmentRepository;
use App\Service\MessageAttachmentService;
use League\Flysystem\FilesystemOperator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class MessageAttachmentServiceTest extends TestCase
{
    public function messageAttachmentServiceMockedDependencyProvider(): array
    {
        return [[
            'defaultStorage'                  => $this->createMock(FilesystemOperator::class),
            'messageAttachmentRepositoryMock' => $this->createMock(MessageAttachmentRepository::class)
        ]];
    }

    /**
     * @dataProvider messageAttachmentServiceMockedDependencyProvider
     */
    public function testProcessAttachmentsUpload(
        FilesystemOperator          $defaultStorage,
        MessageAttachmentRepository $messageAttachmentRepository
    ): void {
        $uploadedFile1 = $this->createMock(UploadedFile::class);
        $uploadedFile2 = $this->createMock(UploadedFile::class);

        $messageAttachmentSercvice = new MessageAttachmentService(
            $defaultStorage,
            $messageAttachmentRepository
        );

        $uploadedFile1
            ->expects($this->once())
            ->method('getClientMimeType')
            ->willReturn('image/png');

        $uploadedFile1
            ->expects($this->once())
            ->method('getClientOriginalExtension')
            ->willReturn('png');

        $uploadedFile1
            ->expects($this->once())
            ->method('getContent')
            ->willReturn('2137');

        $uploadedFile2
            ->expects($this->once())
            ->method('getClientMimeType')
            ->willReturn('image/jpeg');

        $uploadedFile2
            ->expects($this->once())
            ->method('getClientOriginalExtension')
            ->willReturn('jpg');

        $uploadedFile2
            ->expects($this->once())
            ->method('getContent')
            ->willReturn('2138');

        $uploadedFiles = [
            $uploadedFile1,
            $uploadedFile2
        ];

        $result = $messageAttachmentSercvice->processAttachmentUpload(
            $uploadedFiles,
            21,
            37
        );

        $this->assertIsArray($result);
        $this->assertContainsOnly('string', $result);
    }

    /**
     * @dataProvider messageAttachmentServiceMockedDependencyProvider
     */
    public function testProcessAttachmentsDataStore(
        FilesystemOperator                     $defaultStorage,
        MessageAttachmentRepository|MockObject $messageAttachmentRepository
    ): void {
        $message = new Message();

        $uploadedFile1 = $this->createMock(UploadedFile::class);
        $uploadedFile2 = $this->createMock(UploadedFile::class);

        $attachment = new MessageAttachment();

        $messageAttachmentSercvice = new MessageAttachmentService(
            $defaultStorage,
            $messageAttachmentRepository
        );

        $uploadedFiles = [
            $uploadedFile1,
            $uploadedFile2
        ];

        $paths = [
            'uploadedFilePath1',
            'uploadedFilePath2'
        ];

        $messageAttachmentRepository
            ->expects($this->exactly(2))
            ->method('storeAttachment')
            ->willReturn($attachment);

        $result = $messageAttachmentSercvice->processAttachmentsDataStore(
            $uploadedFiles,
            $paths,
            $message
        );

        $this->assertIsArray($result);
        $this->assertContainsOnlyInstancesOf(MessageAttachment::class, $result);
    }
}