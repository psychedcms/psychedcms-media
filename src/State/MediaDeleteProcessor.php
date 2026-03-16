<?php

declare(strict_types=1);

namespace PsychedCms\Media\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Doctrine\ORM\EntityManagerInterface;
use League\Flysystem\FilesystemOperator;
use PsychedCms\Media\Entity\Media;

/**
 * @implements ProcessorInterface<Media, void>
 */
class MediaDeleteProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly FilesystemOperator $defaultStorage,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @param Media $data
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): void
    {
        $storagePath = $data->getStoragePath();

        if ($storagePath !== null && $this->defaultStorage->fileExists($storagePath)) {
            $this->defaultStorage->delete($storagePath);
        }

        // Delete optimized variants
        $variants = $data->getOptimizedVariants();
        if ($variants !== null) {
            foreach ($variants as $variant) {
                $variantPath = $variant['storagePath'] ?? null;
                if ($variantPath !== null && $this->defaultStorage->fileExists($variantPath)) {
                    $this->defaultStorage->delete($variantPath);
                }
            }
        }

        $this->entityManager->remove($data);
        $this->entityManager->flush();
    }
}
