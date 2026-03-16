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
    public function validate(UploadedFile $file): void;

    /**
     * @throws \PsychedCms\Media\Exception\StorageQuotaExceededException
     */
    public function validateQuota(int $currentTotal, int $newFileSize, int $quota): void;
}
