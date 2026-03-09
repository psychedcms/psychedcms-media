<?php

declare(strict_types=1);

namespace PsychedCms\Media\Repository;

use PsychedCms\Media\Entity\Media;

interface MediaRepositoryInterface
{
    /**
     * @return iterable<Media>
     */
    public function findByMimeType(string $mimeType): iterable;

    public function save(Media $media): void;

    public function delete(Media $media): void;
}
