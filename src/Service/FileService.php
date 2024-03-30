<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Constants\Constant;
use App\Entity\MessageAttachment;
use App\Repository\MessageAttachmentRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Expr\Comparison;
use Imagine\Gd\Imagine;
use Imagine\Image\Box;
use Imagine\Image\ImageInterface;
use Pagerfanta\Adapter\ArrayAdapter;
use Pagerfanta\Pagerfanta;

class FileService
{
    private Imagine            $imagine;

    public function __construct(
        private MessageAttachmentRepository $messageAttachmentRepository,
    ) {
        $this->imagine = new Imagine();
    }

    /**
     * resizing image
     *
     * @param  MessageAttachments $img
     * @return ImageInterface
     */
    public function resizeImg(MessageAttachment $img): ImageInterface
    {
        $filename = sprintf(Constant::FILE_STORAGE_PATH, $img->getPath());

        [$iwidth, $iheight] = getimagesize($filename);

        $ratio  = $iwidth / $iheight;
        $width  = Constant::MAX_RESIZED_WIDTH;
        $height = Constant::MAX_RESIZED_HEIGHT;

        if ($width / $height > $ratio) {
            $width = $height * $ratio;
        } else {
            $height = $width / $ratio;
        }

        $img = $this->imagine->open($filename);

        return $img->resize(new Box($width, $height));
    }

    /**
     * get attachments pager
     *
     * @param  int                      $page
     * @param  Collection<int, Message> $messages
     * @param  ?int                     $currentAttachmentId
     * @return Pagerfanta
     */
    public function getAttachmentsPager(int $page, Collection $messages, ?int $currentAttachmenId = null): Pagerfanta
    {
        $attachmentsArray  = $this->getConversationAttachmentsArray($messages);

        if ($currentAttachmenId) {
            $currentAttachment = $this->messageAttachmentRepository->find($currentAttachmenId);
            $attachmentIndex   = array_search($currentAttachment, $attachmentsArray);
            $page              = (int) ceil(($attachmentIndex + 1)/Constant::MAX_IMGS_CAROUSEL_PAGE);
        }

        $adapter = new ArrayAdapter($attachmentsArray);

        return Pagerfanta::createForCurrentPageWithMaxPerPage (
            $adapter,
            $page,
            Constant::MAX_IMGS_CAROUSEL_PAGE,
        );
    }

    /**
     * retrives all attachments from conversation
     *
     * @param  Collection<int, Message> $messages
     * @return MessageAttachment[]
     */
    public function getConversationAttachmentsArray(Collection $messages): array
    {
        $messages   = new ArrayCollection($messages->toArray());
        $criteria   = new Criteria();
        $comparison = new Comparison('attachment', Comparison::EQ, true);

        $criteria->andWhere($comparison);

        $messagesWithAttachments = $messages->matching($criteria)->toArray();

        $attachments = [];

        foreach ($messagesWithAttachments as $message) {
            foreach($message->getMessageAttachments() as $attachment) {
                array_push($attachments, $attachment);
            }
        }

        return $attachments;
    }
}