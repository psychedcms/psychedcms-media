<?php

declare(strict_types=1);

namespace PsychedCms\Media\Exception;

class InvalidFileTypeException extends \RuntimeException implements MediaExceptionInterface
{
    public function __construct(string $mimeType)
    {
        parent::__construct(\sprintf('File type "%s" is not allowed.', $mimeType));
    }
}
