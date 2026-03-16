<?php

declare(strict_types=1);

namespace PsychedCms\Media\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Doctrine\ORM\EntityManagerInterface;
use PsychedCms\Media\Dto\BulkCategorizeInput;
use PsychedCms\Media\Entity\Media;
use PsychedCms\Media\Entity\MediaCategory;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Uid\Ulid;

/**
 * @implements ProcessorInterface<BulkCategorizeInput, array>
 */
class MediaBulkCategorizeProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): array
    {
        if (!$data instanceof BulkCategorizeInput) {
            throw new BadRequestHttpException('Invalid input.');
        }

        $mediaRepository = $this->entityManager->getRepository(Media::class);
        $categoryRepository = $this->entityManager->getRepository(MediaCategory::class);

        $categories = [];
        foreach ($data->categoryIds as $categoryId) {
            $category = $categoryRepository->find(Ulid::fromString($categoryId));
            if ($category !== null) {
                $categories[] = $category;
            }
        }

        if ($categories === []) {
            throw new BadRequestHttpException('No valid categories found.');
        }

        $updated = 0;
        foreach ($data->mediaIds as $mediaId) {
            $media = $mediaRepository->find(Ulid::fromString($mediaId));
            if ($media === null) {
                continue;
            }

            foreach ($categories as $category) {
                if ($data->action === 'remove') {
                    $media->removeCategory($category);
                } else {
                    $media->addCategory($category);
                }
            }
            ++$updated;
        }

        $this->entityManager->flush();

        return ['updated' => $updated];
    }
}
