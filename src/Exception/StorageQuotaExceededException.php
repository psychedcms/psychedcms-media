<?php

declare(strict_types=1);

namespace PsychedCms\Media\Exception;

use Symfony\Component\HttpKernel\Exception\HttpException;

class StorageQuotaExceededException extends HttpException implements MediaExceptionInterface
{
    public function __construct(int $currentSize, int $fileSize, int $quota)
    {
        $message = \sprintf(
            'Storage quota exceeded. Current: %s, file: %s, quota: %s.',
            $this->formatBytes($currentSize),
            $this->formatBytes($fileSize),
            $this->formatBytes($quota),
        );

        parent::__construct(413, $message);
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        $value = (float) $bytes;
        while ($value >= 1024 && $i < \count($units) - 1) {
            $value /= 1024;
            ++$i;
        }

        return round($value, 1) . ' ' . $units[$i];
    }
}
