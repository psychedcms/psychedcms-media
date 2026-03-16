<?php

declare(strict_types=1);

namespace PsychedCms\Media\Service;

interface ExifExtractorInterface
{
    /**
     * @return array<string, mixed>|null
     */
    public function extract(string $filePath, string $mimeType): ?array;
}
