<?php

declare(strict_types=1);

namespace PsychedCms\Media\Controller;

use PsychedCms\Media\Service\FileValidatorInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class UploadConfigController
{
    public function __construct(
        private readonly FileValidatorInterface $fileValidator,
    ) {
    }

    #[Route('/api/media/config', name: 'api_media_config', methods: ['GET'], priority: 10)]
    public function config(): JsonResponse
    {
        return new JsonResponse([
            'allowedTypes' => $this->fileValidator->getAllowedTypes(),
            'maxSizes' => $this->fileValidator->getMaxSizes(),
        ]);
    }
}
