<?php

declare(strict_types=1);

namespace PsychedCms\Media\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Doctrine\ORM\EntityManagerInterface;
use League\Flysystem\FilesystemOperator;
use PsychedCms\Media\Entity\Media;
use PsychedCms\Media\Service\ExifExtractorInterface;
use PsychedCms\Media\Service\FileValidatorInterface;
use PsychedCms\Media\Service\UploadPathResolverInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * @implements ProcessorInterface<Media, Media>
 */
class MediaReplaceProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly FilesystemOperator $defaultStorage,
        private readonly EntityManagerInterface $entityManager,
        private readonly RequestStack $requestStack,
        private readonly FileValidatorInterface $fileValidator,
        private readonly UploadPathResolverInterface $uploadPathResolver,
        private readonly ExifExtractorInterface $exifExtractor,
        private readonly Security $security,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): Media
    {
        $request = $this->requestStack->getCurrentRequest();
        if ($request === null) {
            throw new BadRequestHttpException('No request available.');
        }

        $uploadedFile = $request->files->get('file');
        if ($uploadedFile === null) {
            throw new BadRequestHttpException('No file uploaded.');
        }

        $sizeOverride = $request->headers->get('X-Size-Override') === 'acknowledged';
        $isAdmin = $this->security->isGranted('ROLE_ADMIN');
        $this->fileValidator->validate($uploadedFile, skipSizeCheck: $sizeOverride && $isAdmin);

        // Delete old file
        $oldPath = $data->getStoragePath();
        if ($oldPath !== null && $this->defaultStorage->fileExists($oldPath)) {
            $this->defaultStorage->delete($oldPath);
        }

        // Delete old variants
        $oldVariants = $data->getOptimizedVariants();
        if ($oldVariants !== null) {
            foreach ($oldVariants as $variant) {
                $variantPath = $variant['storagePath'] ?? null;
                if ($variantPath !== null && $this->defaultStorage->fileExists($variantPath)) {
                    $this->defaultStorage->delete($variantPath);
                }
            }
            $data->setOptimizedVariants(null);
        }

        // Upload new file
        $originalFilename = $uploadedFile->getClientOriginalName();
        $sanitizedFilename = $this->uploadPathResolver->sanitizeFilename($originalFilename);
        $dir = $request->request->get('directory') ?? $request->request->get('contentType');
        $directory = $this->uploadPathResolver->resolve(\is_string($dir) ? $dir : null);
        $storagePath = $directory . $sanitizedFilename;

        $stream = fopen($uploadedFile->getPathname(), 'r');
        $this->defaultStorage->writeStream($storagePath, $stream, [
            'visibility' => 'public',
        ]);
        if (\is_resource($stream)) {
            fclose($stream);
        }

        $mimeType = $uploadedFile->getMimeType() ?? $uploadedFile->getClientMimeType();
        $checksum = hash_file('sha256', $uploadedFile->getPathname());

        $data->setFilename($sanitizedFilename);
        $data->setOriginalFilename($originalFilename);
        $data->setMimeType($mimeType);
        $data->setSize($uploadedFile->getSize());
        $data->setStoragePath($storagePath);
        $data->setChecksum($checksum ?: null);
        $data->setWidth(null);
        $data->setHeight(null);
        $data->setExifData(null);

        if (str_starts_with($mimeType, 'image/') && $mimeType !== 'image/svg+xml') {
            $imageSize = @getimagesize($uploadedFile->getPathname());
            if ($imageSize !== false) {
                $data->setWidth($imageSize[0]);
                $data->setHeight($imageSize[1]);
            }
        }

        $exifData = $this->exifExtractor->extract($uploadedFile->getPathname(), $mimeType);
        if ($exifData !== null) {
            $data->setExifData($exifData);
        }

        $this->entityManager->flush();

        return $data;
    }
}
