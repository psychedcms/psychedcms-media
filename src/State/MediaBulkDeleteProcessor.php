<?php

declare(strict_types=1);

namespace PsychedCms\Media\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Doctrine\ORM\EntityManagerInterface;
use League\Flysystem\FilesystemOperator;
use PsychedCms\Media\Dto\BulkDeleteInput;
use PsychedCms\Media\Entity\Media;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Uid\Ulid;

/**
 * @implements ProcessorInterface<BulkDeleteInput, array>
 */
class MediaBulkDeleteProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly FilesystemOperator $defaultStorage,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): array
    {
        if (!$data instanceof BulkDeleteInput) {
            throw new BadRequestHttpException('Invalid input.');
        }

        $deleted = 0;
        $repository = $this->entityManager->getRepository(Media::class);

        foreach ($data->ids as $id) {
            $media = $repository->find(Ulid::fromString($id));
            if ($media === null) {
                continue;
            }

            $storagePath = $media->getStoragePath();
            if ($storagePath !== null && $this->defaultStorage->fileExists($storagePath)) {
                $this->defaultStorage->delete($storagePath);
            }

            // Delete optimized variants
            $variants = $media->getOptimizedVariants();
            if ($variants !== null) {
                foreach ($variants as $variant) {
                    $variantPath = $variant['storagePath'] ?? null;
                    if ($variantPath !== null && $this->defaultStorage->fileExists($variantPath)) {
                        $this->defaultStorage->delete($variantPath);
                    }
                }
            }

            $this->entityManager->remove($media);
            ++$deleted;
        }

        $this->entityManager->flush();

        return ['deleted' => $deleted];
    }
}
