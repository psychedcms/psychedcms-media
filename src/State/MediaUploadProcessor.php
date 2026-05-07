<?php

declare(strict_types=1);

namespace PsychedCms\Media\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use PsychedCms\Media\Entity\Media;
use PsychedCms\Media\Message\CreateMediaCommand;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Thin adapter: extracts upload payload from the HTTP request and dispatches a
 * CreateMediaCommand through the message bus. All business logic (validation,
 * checksum-based deduplication, storage write, persistence) lives in MediaService.
 *
 * @implements ProcessorInterface<Media, Media>
 */
class MediaUploadProcessor implements ProcessorInterface
{
    use HandleTrait;

    public function __construct(
        MessageBusInterface $messageBus,
        private readonly RequestStack $requestStack,
        private readonly Security $security,
    ) {
        $this->messageBus = $messageBus;
    }

    /**
     * @param Media $data
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): Media
    {
        $request = $this->requestStack->getCurrentRequest();
        if ($request === null) {
            throw new BadRequestHttpException('No request available.');
        }

        $uploadedFile = $request->files->get('file');
        if ($uploadedFile === null) {
            throw new BadRequestHttpException('No file uploaded. Send file as "file" field in multipart/form-data.');
        }

        $buffer = \file_get_contents($uploadedFile->getPathname());
        if ($buffer === false) {
            throw new BadRequestHttpException('Failed to read uploaded file.');
        }

        $mimeType = $uploadedFile->getMimeType() ?? $uploadedFile->getClientMimeType() ?? 'application/octet-stream';
        $originalFilename = $uploadedFile->getClientOriginalName();

        $sizeOverride = $request->headers->get('X-Size-Override') === 'acknowledged';
        $isAdmin = $this->security->isGranted('ROLE_ADMIN');

        $directory = $request->request->get('directory') ?? $request->request->get('contentType');
        $altText = $request->request->get('altText');
        $credits = $request->request->get('credits');
        $title = $request->request->get('title');
        $description = $request->request->get('description');
        $storage = $request->request->get('storage');

        $command = new CreateMediaCommand(
            buffer: $buffer,
            directory: \is_string($directory) ? $directory : '',
            filename: $originalFilename,
            mimeType: $mimeType,
            altText: \is_string($altText) ? $altText : null,
            credits: \is_string($credits) ? $credits : null,
            title: \is_string($title) ? $title : null,
            description: \is_string($description) ? $description : null,
            storage: \is_string($storage) && $storage !== '' ? $storage : null,
            skipSizeCheck: $sizeOverride && $isAdmin,
            checkQuota: true,
        );

        return $this->handle($command);
    }
}
