<?php

declare(strict_types=1);

namespace PsychedCms\Media\Service;

interface StorageStatsProviderInterface
{
    /**
     * @return array<string, mixed>
     */
    public function getStats(): array;
}
