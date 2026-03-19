<?php

declare(strict_types=1);

namespace PsychedCms\Media\Service;

use PsychedCms\Media\Exception\FileSizeExceededException;
use PsychedCms\Media\Exception\InvalidFileTypeException;
use PsychedCms\Media\Exception\StorageQuotaExceededException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class FileValidator implements FileValidatorInterface
{
    private const DEFAULT_MAX_SIZES = [
        'image' => 10 * 1024 * 1024,    // 10MB
        'video' => 200 * 1024 * 1024,   // 200MB
        'audio' => 50 * 1024 * 1024,    // 50MB
        'document' => 20 * 1024 * 1024, // 20MB
    ];

    private const DEFAULT_ALLOWED_TYPES = [
        // Images
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
        'image/avif',
        'image/svg+xml',
        // Documents
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'text/plain',
        'text/csv',
        // Video
        'video/mp4',
        'video/webm',
        // Audio
        'audio/mpeg',
        'audio/ogg',
        'audio/wav',
    ];

    /** @var string[] */
    private readonly array $allowedTypes;

    /** @var array{image: int, video: int, audio: int, document: int} */
    private readonly array $maxSizes;

    /**
     * @param string[] $allowedTypes
     * @param array<string, int> $maxSizes
     */
    public function __construct(
        array $allowedTypes = [],
        array $maxSizes = [],
    ) {
        $this->allowedTypes = $allowedTypes === [] ? self::DEFAULT_ALLOWED_TYPES : $allowedTypes;
        $this->maxSizes = array_merge(self::DEFAULT_MAX_SIZES, $maxSizes);
    }

    public function validate(UploadedFile $file, bool $skipSizeCheck = false): void
    {
        $mimeType = $file->getMimeType() ?? $file->getClientMimeType();

        if (!\in_array($mimeType, $this->allowedTypes, true)) {
            throw new InvalidFileTypeException($mimeType ?? 'unknown');
        }

        if (!$skipSizeCheck) {
            $maxSize = $this->getMaxSizeForMimeType($mimeType ?? '');
            if ($file->getSize() > $maxSize) {
                throw new FileSizeExceededException($file->getSize(), $maxSize);
            }
        }

        if ($mimeType === 'image/svg+xml') {
            $this->scanSvgForScripts($file);
        }
    }

    /** @return string[] */
    public function getAllowedTypes(): array
    {
        return $this->allowedTypes;
    }

    /** @return array{image: int, video: int, audio: int, document: int} */
    public function getMaxSizes(): array
    {
        return $this->maxSizes;
    }

    private function getMaxSizeForMimeType(string $mimeType): int
    {
        if (str_starts_with($mimeType, 'image/')) {
            return $this->maxSizes['image'];
        }
        if (str_starts_with($mimeType, 'video/')) {
            return $this->maxSizes['video'];
        }
        if (str_starts_with($mimeType, 'audio/')) {
            return $this->maxSizes['audio'];
        }

        return $this->maxSizes['document'];
    }

    public function validateQuota(int $currentTotal, int $newFileSize, int $quota): void
    {
        if (($currentTotal + $newFileSize) > $quota) {
            throw new StorageQuotaExceededException($currentTotal, $newFileSize, $quota);
        }
    }

    private function scanSvgForScripts(UploadedFile $file): void
    {
        $content = file_get_contents($file->getPathname());
        if ($content === false) {
            return;
        }

        $dangerous = [
            '/<script\b/i',
            '/\bon\w+\s*=/i',
            '/javascript\s*:/i',
            '/data\s*:\s*text\/html/i',
        ];

        foreach ($dangerous as $pattern) {
            if (preg_match($pattern, $content)) {
                throw new InvalidFileTypeException('image/svg+xml (contains potentially dangerous content)');
            }
        }
    }
}
