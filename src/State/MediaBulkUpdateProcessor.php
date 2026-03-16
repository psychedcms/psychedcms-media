<?php

declare(strict_types=1);

namespace PsychedCms\Media\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Doctrine\ORM\EntityManagerInterface;
use PsychedCms\Media\Dto\BulkUpdateInput;
use PsychedCms\Media\Entity\Media;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Uid\Ulid;

/**
 * @implements ProcessorInterface<BulkUpdateInput, array>
 */
class MediaBulkUpdateProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): array
    {
        if (!$data instanceof BulkUpdateInput) {
            throw new BadRequestHttpException('Invalid input.');
        }

        $repository = $this->entityManager->getRepository(Media::class);
        $updated = 0;

        foreach ($data->ids as $id) {
            $media = $repository->find(Ulid::fromString($id));
            if ($media === null) {
                continue;
            }

            if ($data->altText !== null) {
                $media->setAltText($data->altText);
            }
            if ($data->title !== null) {
                $media->setTitle($data->title);
            }
            if ($data->description !== null) {
                $media->setDescription($data->description);
            }
            ++$updated;
        }

        $this->entityManager->flush();

        return ['updated' => $updated];
    }
}
