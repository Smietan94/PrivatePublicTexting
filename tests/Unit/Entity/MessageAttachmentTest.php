<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\Message;
use App\Entity\MessageAttachment;
use PHPUnit\Framework\TestCase;

class MessageAttachmentTest extends TestCase
{
    public function testSetAndGetFileName(): void
    {
        $messageAttachment = new MessageAttachment();
        $fileName          = 'testFileName.jpg';

        $this->assertNull($messageAttachment->getFileName());

        $messageAttachment->setFileName($fileName);

        $this->assertIsString($messageAttachment->getFileName());
        $this->assertSame($fileName, $messageAttachment->getFileName());
    }

    public function testSetAndGetExtension(): void
    {
        $messageAttachment = new MessageAttachment();
        $fileExtension     = 'jpg';

        $this->assertNull($messageAttachment->getExtension());

        $messageAttachment->setExtension($fileExtension);

        $this->assertIsString($messageAttachment->getExtension());
        $this->assertSame($fileExtension, $messageAttachment->getExtension());
    }

    public function testSetAndGetMimeType(): void
    {
        $messageAttachment = new MessageAttachment();
        $fileMimeType      = 'image/jpeg';

        $this->assertNull($messageAttachment->getMimeType());

        $messageAttachment->setMimeType($fileMimeType);

        $this->assertIsString($messageAttachment->getMimeType());
        $this->assertSame($fileMimeType, $messageAttachment->getMimeType());
    }

    public function testSetAndGetPath(): void
    {
        $messageAttachment = new MessageAttachment();
        $filePath          = '/testFilePath/testFileName.jpg';

        $this->assertNull($messageAttachment->getPath());

        $messageAttachment->setPath($filePath);

        $this->assertIsString($messageAttachment->getPath());
        $this->assertSame($filePath, $messageAttachment->getPath());
    }

    public function testSetAndGetMessage(): void
    {
        $messageAttachment = new MessageAttachment();
        $message           = new Message();

        $this->assertNull($messageAttachment->getMessage());

        $messageAttachment->setMessage($message);

        $this->assertInstanceOf(Message::class, $messageAttachment->getMessage());
        $this->assertSame($message, $messageAttachment->getMessage());
    }

    public function testTimestamps(): void
    {
        $messageAttachment = new MessageAttachment();

        $this->assertNull($messageAttachment->getCreatedAt());
        $this->assertNull($messageAttachment->getUpdatedAt());

        $messageAttachment->setCreatedAt(new \DateTime());
        $messageAttachment->setUpdatedAt(new \DateTime());

        $this->assertInstanceOf(\DateTimeInterface::class, $messageAttachment->getCreatedAt());
        $this->assertInstanceOf(\DateTimeInterface::class, $messageAttachment->getUpdatedAt());
    }
}