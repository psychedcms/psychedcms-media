<?php

declare(strict_types=1);

namespace PsychedCms\Media\Service;

interface ImageOptimizerInterface
{
    /**
     * Optimize an image and generate variants (WebP, AVIF).
     *
     * @return array<string, array{storagePath: string, mimeType: string, size: int}>|null
     */
    public function optimize(string $localPath, string $storagePath, string $mimeType): ?array;
}
