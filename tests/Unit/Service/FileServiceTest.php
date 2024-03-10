<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\Constants\Constant;
use App\Entity\Message;
use App\Entity\MessageAttachment;
use App\Repository\MessageAttachmentRepository;
use App\Service\FileService;
use Doctrine\Common\Collections\ArrayCollection;
use Imagine\Image\ImageInterface;
use PHPUnit\Framework\TestCase;

class FileServiceTest extends TestCase
{
    public function fileServiceMockDependencyProvider(): array
    {
        return [[
            'messageAttachmentRepositoryMock' => $this->createMock(MessageAttachmentRepository::class)
        ]];
    }

    public function fileServiceProvider(): array
    {
        $messageAttachmentRepositoryMock = $this->createMock(MessageAttachmentRepository::class);

        return [[
            'fileService' => new FileService(
                $messageAttachmentRepositoryMock
            )
        ]];
    }

    /**
     * @dataProvider fileServiceProvider
     */
    public function testReseizeImg(FileService $fileService): void
    {
        chdir(getcwd().'/public/');
        $image = imagecreatetruecolor(50, 50);
        imagepng($image, '../var/storage/testFolder/fileName.png');
        $messageAttachment = $this->createMock(MessageAttachment::class);

        $messageAttachment
            ->expects($this->once())
            ->method('getPath')
            ->willReturn('/testFolder/fileName.png');

        $result = $fileService->resizeImg($messageAttachment);

        $this->assertInstanceOf(ImageInterface::class, $result);
        imagedestroy($image);
    }

    /**
     * @dataProvider fileServiceProvider
     */
    public function testgetConversationAttachmentsArray(FileService $fileService): void
    {
        $message1 = $this->createMock(Message::class);
        $message2 = $this->createMock(Message::class);
        $message3 = $this->createMock(Message::class);

        $messageAttachment1 = $this->createMock(MessageAttachment::class);
        $messageAttachment2 = $this->createMock(MessageAttachment::class);
        $messageAttachment3 = $this->createMock(MessageAttachment::class);

        $message1
            ->expects($this->once())
            ->method('isAttachment')
            ->willReturn(true);

        $message3
            ->expects($this->once())
            ->method('isAttachment')
            ->willReturn(true);

        $message1
            ->expects($this->once())
            ->method('getMessageAttachments')
            ->willReturn(new ArrayCollection([$messageAttachment1]));

        $message3
            ->expects($this->once())
            ->method('getMessageAttachments')
            ->willReturn(new ArrayCollection([$messageAttachment2, $messageAttachment3]));

        $result = $fileService->getConversationAttachmentsArray(new ArrayCollection([$message1, $message2, $message3]));

        $this->assertIsArray($result);
        $this->assertContainsOnlyInstancesOf(MessageAttachment::class, $result);
    }
}