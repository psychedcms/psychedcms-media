<?php

declare(strict_types=1);

namespace PsychedCms\Media\Service;

use PsychedCms\Media\Exception\FileSizeExceededException;
use PsychedCms\Media\Exception\InvalidFileTypeException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class FileValidator implements FileValidatorInterface
{
    private const DEFAULT_MAX_SIZE = 10 * 1024 * 1024; // 10MB

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

    /**
     * @param string[] $allowedTypes
     */
    public function __construct(
        private readonly array $allowedTypes = self::DEFAULT_ALLOWED_TYPES,
        private readonly int $maxSize = self::DEFAULT_MAX_SIZE,
    ) {
    }

    public function validate(UploadedFile $file): void
    {
        $mimeType = $file->getMimeType() ?? $file->getClientMimeType();

        if (!\in_array($mimeType, $this->allowedTypes, true)) {
            throw new InvalidFileTypeException($mimeType ?? 'unknown');
        }

        if ($file->getSize() > $this->maxSize) {
            throw new FileSizeExceededException($file->getSize(), $this->maxSize);
        }

        if ($mimeType === 'image/svg+xml') {
            $this->scanSvgForScripts($file);
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
