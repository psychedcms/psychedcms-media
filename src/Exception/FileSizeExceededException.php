<?php

declare(strict_types=1);

namespace PsychedCms\Media\Exception;

class FileSizeExceededException extends \RuntimeException implements MediaExceptionInterface
{
    public function __construct(int $size, int $maxSize)
    {
        parent::__construct(\sprintf(
            'File size %d bytes exceeds maximum allowed size of %d bytes.',
            $size,
            $maxSize,
        ));
    }
}
