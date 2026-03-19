<?php

declare(strict_types=1);

namespace PsychedCms\Media\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Doctrine\ORM\EntityManagerInterface;
use League\Flysystem\FilesystemOperator;
use Psr\Container\ContainerInterface;
use PsychedCms\Media\Entity\Media;
use PsychedCms\Media\Repository\MediaRepositoryInterface;
use PsychedCms\Media\Service\ExifExtractorInterface;
use PsychedCms\Media\Service\FileValidatorInterface;
use PsychedCms\Media\Service\UploadPathResolverInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * @implements ProcessorInterface<Media, Media>
 */
class MediaUploadProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly FilesystemOperator $defaultStorage,
        private readonly EntityManagerInterface $entityManager,
        private readonly RequestStack $requestStack,
        private readonly FileValidatorInterface $fileValidator,
        private readonly UploadPathResolverInterface $uploadPathResolver,
        private readonly MediaRepositoryInterface $mediaRepository,
        private readonly ExifExtractorInterface $exifExtractor,
        private readonly Security $security,
        private readonly int $storageQuota = 0,
        private readonly ?ContainerInterface $storageLocator = null,
    ) {
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

        $sizeOverride = $request->headers->get('X-Size-Override') === 'acknowledged';
        $isAdmin = $this->security->isGranted('ROLE_ADMIN');
        $this->fileValidator->validate($uploadedFile, skipSizeCheck: $sizeOverride && $isAdmin);

        // Quota check
        if ($this->storageQuota > 0) {
            $currentTotal = $this->mediaRepository->getTotalStorageSize();
            $this->fileValidator->validateQuota($currentTotal, $uploadedFile->getSize(), $this->storageQuota);
        }

        $originalFilename = $uploadedFile->getClientOriginalName();
        $sanitizedFilename = $this->uploadPathResolver->sanitizeFilename($originalFilename);
        $dir = $request->request->get('directory') ?? $request->request->get('contentType');
        $resolvedDir = $this->uploadPathResolver->resolve(\is_string($dir) ? $dir : null);
        $storagePath = $resolvedDir . $sanitizedFilename;

        $storage = $this->resolveStorage($request->request->get('storage'));
        $stream = fopen($uploadedFile->getPathname(), 'r');
        $storage->writeStream($storagePath, $stream, [
            'visibility' => 'public',
        ]);
        if (\is_resource($stream)) {
            fclose($stream);
        }

        $mimeType = $uploadedFile->getMimeType() ?? $uploadedFile->getClientMimeType();

        // SHA-256 checksum
        $checksum = hash_file('sha256', $uploadedFile->getPathname());

        $media = new Media();
        $media->setFilename($sanitizedFilename);
        $media->setOriginalFilename($originalFilename);
        $media->setMimeType($mimeType);
        $media->setSize($uploadedFile->getSize());
        $media->setStoragePath($storagePath);
        $media->setChecksum($checksum ?: null);

        if (str_starts_with($mimeType, 'image/') && $mimeType !== 'image/svg+xml') {
            $imageSize = @getimagesize($uploadedFile->getPathname());
            if ($imageSize !== false) {
                $media->setWidth($imageSize[0]);
                $media->setHeight($imageSize[1]);
            }
        }

        // EXIF extraction (JPEG/TIFF only)
        $exifData = $this->exifExtractor->extract($uploadedFile->getPathname(), $mimeType);
        if ($exifData !== null) {
            $media->setExifData($exifData);
        }

        $altText = $request->request->get('altText');
        if ($altText !== null) {
            $media->setAltText($altText);
        }

        $title = $request->request->get('title');
        if ($title !== null) {
            $media->setTitle($title);
        }

        $description = $request->request->get('description');
        if ($description !== null) {
            $media->setDescription($description);
        }

        $storageName = $request->request->get('storage');
        $media->setStorage(\is_string($storageName) && $storageName !== '' ? $storageName : 'content');

        $this->entityManager->persist($media);
        $this->entityManager->flush();

        return $media;
    }

    private function resolveStorage(?string $storageName): FilesystemOperator
    {
        if ($storageName === null || $storageName === '' || $storageName === 'content') {
            return $this->defaultStorage;
        }

        $key = $storageName . '.storage';
        if ($this->storageLocator !== null && $this->storageLocator->has($key)) {
            return $this->storageLocator->get($key);
        }

        return $this->defaultStorage;
    }
}
