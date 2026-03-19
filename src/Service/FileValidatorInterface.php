<?php

declare(strict_types=1);

namespace PsychedCms\Media\Service;

use Symfony\Component\HttpFoundation\File\UploadedFile;

interface FileValidatorInterface
{
    /**
     * @throws \PsychedCms\Media\Exception\InvalidFileTypeException
     * @throws \PsychedCms\Media\Exception\FileSizeExceededException
     */
    public function validate(UploadedFile $file, bool $skipSizeCheck = false): void;

    /**
     * @throws \PsychedCms\Media\Exception\StorageQuotaExceededException
     */
    public function validateQuota(int $currentTotal, int $newFileSize, int $quota): void;

    /** @return string[] */
    public function getAllowedTypes(): array;

    /** @return array{image: int, video: int, audio: int, document: int} */
    public function getMaxSizes(): array;
}
