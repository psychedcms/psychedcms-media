<?php

declare(strict_types=1);

namespace PsychedCms\Media\Service;

use League\Flysystem\FilesystemOperator;
use PsychedCms\Media\Repository\MediaRepositoryInterface;

class OrphanedFileDetector implements OrphanedFileDetectorInterface
{
    public function __construct(
        private readonly FilesystemOperator $defaultStorage,
        private readonly MediaRepositoryInterface $mediaRepository,
    ) {
    }

    public function detect(): array
    {
        // Get all storage paths from database
        $knownPaths = $this->mediaRepository->getAllStoragePaths();
        $knownPathsSet = array_flip($knownPaths);

        // Scan storage for all files
        $orphaned = [];
        $listing = $this->defaultStorage->listContents('', true);

        foreach ($listing as $item) {
            if ($item->isFile()) {
                $path = $item->path();
                if (!isset($knownPathsSet[$path])) {
                    $orphaned[] = $path;
                }
            }
        }

        return $orphaned;
    }
}
