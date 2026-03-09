<?php

declare(strict_types=1);

namespace PsychedCms\Media\Service;

interface UploadPathResolverInterface
{
    public function resolve(?string $contentType = null): string;

    public function sanitizeFilename(string $original): string;
}
