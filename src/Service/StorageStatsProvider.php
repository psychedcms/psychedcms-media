<?php

declare(strict_types=1);

namespace PsychedCms\Media\Service;

use PsychedCms\Media\Repository\MediaRepositoryInterface;

class StorageStatsProvider implements StorageStatsProviderInterface
{
    public function __construct(
        private readonly MediaRepositoryInterface $mediaRepository,
        private readonly int $storageQuota = 0,
    ) {
    }

    public function getStats(): array
    {
        return [
            'totalSize' => $this->mediaRepository->getTotalStorageSize(),
            'quota' => $this->storageQuota,
            'byMimeGroup' => $this->mediaRepository->getStorageStatsByMimeGroup(),
            'largestFiles' => $this->mediaRepository->getLargestFiles(10),
            'totalCount' => $this->mediaRepository->count([]),
        ];
    }
}
