<?php

declare(strict_types=1);

namespace PsychedCms\Media\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use PsychedCms\Media\Entity\Media;

/**
 * @extends ServiceEntityRepository<Media>
 */
class MediaRepository extends ServiceEntityRepository implements MediaRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Media::class);
    }

    public function findByMimeType(string $mimeType): iterable
    {
        return $this->createQueryBuilder('m')
            ->where('m.mimeType = :mimeType')
            ->setParameter('mimeType', $mimeType)
            ->orderBy('m.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByChecksum(string $checksum): ?Media
    {
        return $this->createQueryBuilder('m')
            ->where('m.checksum = :checksum')
            ->setParameter('checksum', $checksum)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function getTotalStorageSize(): int
    {
        $result = $this->createQueryBuilder('m')
            ->select('COALESCE(SUM(m.size), 0)')
            ->getQuery()
            ->getSingleScalarResult();

        return (int) $result;
    }

    public function getStorageStatsByMimeGroup(): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = <<<'SQL'
            SELECT
                SPLIT_PART(mime_type, '/', 1) AS "mimeGroup",
                COALESCE(SUM(size), 0) AS "totalSize",
                COUNT(*) AS "count"
            FROM media
            GROUP BY SPLIT_PART(mime_type, '/', 1)
            ORDER BY "totalSize" DESC
            SQL;

        return $conn->fetchAllAssociative($sql);
    }

    public function getLargestFiles(int $limit = 10): array
    {
        return $this->createQueryBuilder('m')
            ->select('m.id', 'm.originalFilename', 'm.size', 'm.mimeType')
            ->orderBy('m.size', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getArrayResult();
    }

    public function getAllStoragePaths(): array
    {
        $results = $this->createQueryBuilder('m')
            ->select('m.storagePath')
            ->getQuery()
            ->getArrayResult();

        return array_column($results, 'storagePath');
    }

    public function save(Media $media): void
    {
        $this->getEntityManager()->persist($media);
        $this->getEntityManager()->flush();
    }

    public function delete(Media $media): void
    {
        $this->getEntityManager()->remove($media);
        $this->getEntityManager()->flush();
    }
}
