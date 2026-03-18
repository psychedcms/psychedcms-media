<?php

declare(strict_types=1);

namespace PsychedCms\Media\Service;

interface UploadPathResolverInterface
{
    public function resolve(?string $directory = null): string;

    public function sanitizeFilename(string $original): string;
}
