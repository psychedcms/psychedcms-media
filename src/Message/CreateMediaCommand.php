<?php

declare(strict_types=1);

namespace PsychedCms\Media\Message;

final class CreateMediaCommand
{
    public function __construct(
        public readonly string $buffer,
        public readonly string $directory,
        public readonly string $filename,
        public readonly string $mimeType,
        public readonly ?string $altText = null,
        public readonly ?string $credits = null,
        public readonly ?string $title = null,
        public readonly ?string $description = null,
        public readonly ?string $storage = null,
        public readonly bool $skipSizeCheck = false,
        public readonly bool $checkQuota = false,
    ) {}
}
