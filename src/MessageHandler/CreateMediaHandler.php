<?php

declare(strict_types=1);

namespace PsychedCms\Media\MessageHandler;

use PsychedCms\Media\Entity\Media;
use PsychedCms\Media\Message\CreateMediaCommand;
use PsychedCms\Media\Service\MediaServiceInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class CreateMediaHandler
{
    public function __construct(
        private readonly MediaServiceInterface $mediaService,
    ) {}

    public function __invoke(CreateMediaCommand $command): Media
    {
        return $this->mediaService->createFromBuffer(
            buffer: $command->buffer,
            directory: $command->directory,
            filename: $command->filename,
            mimeType: $command->mimeType,
            altText: $command->altText,
            credits: $command->credits,
            title: $command->title,
            description: $command->description,
            storage: $command->storage,
            skipSizeCheck: $command->skipSizeCheck,
            checkQuota: $command->checkQuota,
        );
    }
}
