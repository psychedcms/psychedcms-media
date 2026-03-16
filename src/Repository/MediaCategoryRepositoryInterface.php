<?php

declare(strict_types=1);

namespace PsychedCms\Media\Repository;

use PsychedCms\Media\Entity\MediaCategory;

interface MediaCategoryRepositoryInterface
{
    public function findBySlug(string $slug): ?MediaCategory;

    public function save(MediaCategory $category): void;

    public function delete(MediaCategory $category): void;
}
