<?php

declare(strict_types=1);

namespace PsychedCms\Media\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use PsychedCms\Media\Entity\MediaCategory;

/**
 * @extends ServiceEntityRepository<MediaCategory>
 */
class MediaCategoryRepository extends ServiceEntityRepository implements MediaCategoryRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MediaCategory::class);
    }

    public function findBySlug(string $slug): ?MediaCategory
    {
        return $this->findOneBy(['slug' => $slug]);
    }

    public function save(MediaCategory $category): void
    {
        $this->getEntityManager()->persist($category);
        $this->getEntityManager()->flush();
    }

    public function delete(MediaCategory $category): void
    {
        $this->getEntityManager()->remove($category);
        $this->getEntityManager()->flush();
    }
}
