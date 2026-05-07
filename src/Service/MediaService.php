<?php

declare(strict_types=1);

namespace PsychedCms\Media\Service;

use finfo;
use InvalidArgumentException;
use League\Flysystem\FilesystemOperator;
use Psr\Container\ContainerInterface;
use PsychedCms\Media\Dto\MediaCreateInput;
use PsychedCms\Media\Entity\Media;
use PsychedCms\Media\Repository\MediaRepositoryInterface;
use RuntimeException;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class MediaService implements MediaServiceInterface
{
    public function __construct(
        private readonly FilesystemOperator $defaultStorage,
        private readonly MediaRepositoryInterface $mediaRepository,
        private readonly FileValidatorInterface $fileValidator,
        private readonly UploadPathResolverInterface $uploadPathResolver,
        private readonly ExifExtractorInterface $exifExtractor,
        private readonly HttpClientInterface $httpClient,
        private readonly ?ContainerInterface $storageLocator = null,
        private readonly int $storageQuota = 0,
    ) {}

    public function create(MediaCreateInput $input): Media
    {
        $this->fileValidator->validateBuffer($input->buffer, $input->mimeType, $input->skipSizeCheck);

        $size = \strlen($input->buffer);

        if ($input->checkQuota && $this->storageQuota > 0) {
            $currentTotal = $this->mediaRepository->getTotalStorageSize();
            $this->fileValidator->validateQuota($currentTotal, $size, $this->storageQuota);
        }

        $checksum = \hash('sha256', $input->buffer);
        $existing = $this->mediaRepository->findByChecksum($checksum);
        if ($existing !== null) {
            return $existing;
        }

        $sanitizedFilename = $this->uploadPathResolver->sanitizeFilename($input->filename);
        $resolvedDir = $this->uploadPathResolver->resolve($input->directory);
        $storagePath = $resolvedDir . $sanitizedFilename;

        $storage = $this->resolveStorage($input->storage);
        $storage->write($storagePath, $input->buffer, [
            'visibility' => 'public',
        ]);

        $media = new Media();
        $media->setFilename($sanitizedFilename);
        $media->setOriginalFilename($input->filename);
        $media->setMimeType($input->mimeType);
        $media->setSize($size);
        $media->setStoragePath($storagePath);
        $media->setStorage($input->storage !== null && $input->storage !== '' ? $input->storage : 'content');
        $media->setChecksum($checksum);

        if (\str_starts_with($input->mimeType, 'image/') && $input->mimeType !== 'image/svg+xml') {
            $this->extractImageDimensions($input->buffer, $media);
        }

        $exifData = $this->extractExifFromBuffer($input->buffer, $input->mimeType);
        if ($exifData !== null) {
            $media->setExifData($exifData);
        }

        if ($input->altText !== null) {
            $media->setAltText($input->altText);
        }
        if ($input->credits !== null) {
            $media->setCredits($input->credits);
        }
        if ($input->title !== null) {
            $media->setTitle($input->title);
        }
        if ($input->description !== null) {
            $media->setDescription($input->description);
        }

        $this->mediaRepository->save($media);

        return $media;
    }

    public function createFromUrl(
        string $url,
        string $directory,
        ?string $altText = null,
        ?string $credits = null,
        ?string $title = null,
        ?string $description = null,
        ?string $storage = null,
    ): Media {
        $response = $this->httpClient->request('GET', $url, [
            'timeout' => 30,
            'max_redirects' => 5,
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (compatible; HiloBot/1.0)',
                'Accept' => 'image/*,*/*',
            ],
        ]);

        if ($response->getStatusCode() !== 200) {
            throw new RuntimeException(\sprintf('Failed to download image from %s: HTTP %d', $url, $response->getStatusCode()));
        }

        $buffer = $response->getContent();
        $headerMime = $response->getHeaders(false)['content-type'][0] ?? null;
        $mimeType = $this->normalizeMimeType($headerMime, $buffer);

        $filename = $this->extractFilenameFromUrl($url);

        return $this->create(new MediaCreateInput(
            buffer: $buffer,
            directory: $directory,
            filename: $filename,
            mimeType: $mimeType,
            altText: $altText,
            credits: $credits,
            title: $title,
            description: $description,
            storage: $storage,
        ));
    }

    public function createFromBase64(
        string $base64,
        string $directory,
        string $filename,
        ?string $mimeTypeHint = null,
        ?string $altText = null,
        ?string $credits = null,
        ?string $title = null,
        ?string $description = null,
        ?string $storage = null,
    ): Media {
        $buffer = \base64_decode($base64, true);
        if ($buffer === false) {
            throw new InvalidArgumentException('Invalid base64 data');
        }

        $mimeType = $this->normalizeMimeType($mimeTypeHint, $buffer);

        return $this->create(new MediaCreateInput(
            buffer: $buffer,
            directory: $directory,
            filename: $filename,
            mimeType: $mimeType,
            altText: $altText,
            credits: $credits,
            title: $title,
            description: $description,
            storage: $storage,
        ));
    }

    public function createFromBuffer(
        string $buffer,
        string $directory,
        string $filename,
        string $mimeType,
        ?string $altText = null,
        ?string $credits = null,
        ?string $title = null,
        ?string $description = null,
        ?string $storage = null,
        bool $skipSizeCheck = false,
        bool $checkQuota = false,
    ): Media {
        return $this->create(new MediaCreateInput(
            buffer: $buffer,
            directory: $directory,
            filename: $filename,
            mimeType: $mimeType,
            altText: $altText,
            credits: $credits,
            title: $title,
            description: $description,
            storage: $storage,
            skipSizeCheck: $skipSizeCheck,
            checkQuota: $checkQuota,
        ));
    }

    public function delete(Media $media): void
    {
        $storage = $this->resolveStorage($media->getStorage());

        $storagePath = $media->getStoragePath();
        if ($storagePath !== null && $storage->fileExists($storagePath)) {
            $storage->delete($storagePath);
        }

        $variants = $media->getOptimizedVariants();
        if ($variants !== null) {
            foreach ($variants as $variant) {
                $variantPath = $variant['storagePath'] ?? null;
                if (\is_string($variantPath) && $storage->fileExists($variantPath)) {
                    $storage->delete($variantPath);
                }
            }
        }

        $this->mediaRepository->delete($media);
    }

    private function resolveStorage(?string $storageName): FilesystemOperator
    {
        if ($storageName === null || $storageName === '' || $storageName === 'content') {
            return $this->defaultStorage;
        }

        $key = $storageName . '.storage';
        if ($this->storageLocator !== null && $this->storageLocator->has($key)) {
            $resolved = $this->storageLocator->get($key);
            if ($resolved instanceof FilesystemOperator) {
                return $resolved;
            }
        }

        return $this->defaultStorage;
    }

    private function normalizeMimeType(?string $hint, string $buffer): string
    {
        if ($hint !== null && $hint !== '') {
            return \explode(';', $hint)[0];
        }

        $finfo = new finfo(\FILEINFO_MIME_TYPE);
        $detected = $finfo->buffer($buffer);

        return \is_string($detected) && $detected !== '' ? $detected : 'application/octet-stream';
    }

    private function extractFilenameFromUrl(string $url): string
    {
        $parsed = \parse_url($url);
        $path = \is_array($parsed) ? ($parsed['path'] ?? '') : '';
        $parts = \explode('/', $path);
        $name = \end($parts);
        $name = \is_string($name) ? \explode('?', $name)[0] : '';

        return $name !== '' ? $name : 'image';
    }

    private function extractImageDimensions(string $buffer, Media $media): void
    {
        $tmp = \tempnam(\sys_get_temp_dir(), 'media_');
        if ($tmp === false) {
            return;
        }
        try {
            \file_put_contents($tmp, $buffer);
            $size = @\getimagesize($tmp);
            if ($size !== false) {
                $media->setWidth($size[0]);
                $media->setHeight($size[1]);
            }
        } finally {
            @\unlink($tmp);
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    private function extractExifFromBuffer(string $buffer, string $mimeType): ?array
    {
        $tmp = \tempnam(\sys_get_temp_dir(), 'media_exif_');
        if ($tmp === false) {
            return null;
        }
        try {
            \file_put_contents($tmp, $buffer);

            return $this->exifExtractor->extract($tmp, $mimeType);
        } finally {
            @\unlink($tmp);
        }
    }
}
