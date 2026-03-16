<?php

declare(strict_types=1);

namespace PsychedCms\Media\Controller;

use PsychedCms\Media\Service\OrphanedFileDetectorInterface;
use PsychedCms\Media\Service\StorageStatsProviderInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class StorageStatsController
{
    public function __construct(
        private readonly StorageStatsProviderInterface $statsProvider,
        private readonly OrphanedFileDetectorInterface $orphanedFileDetector,
    ) {
    }

    #[Route('/api/media/stats', name: 'api_media_stats', methods: ['GET'])]
    public function stats(): JsonResponse
    {
        return new JsonResponse($this->statsProvider->getStats());
    }

    #[Route('/api/media/orphaned', name: 'api_media_orphaned', methods: ['GET'])]
    public function orphaned(): JsonResponse
    {
        return new JsonResponse([
            'orphanedFiles' => $this->orphanedFileDetector->detect(),
        ]);
    }
}
