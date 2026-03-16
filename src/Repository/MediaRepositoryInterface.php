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

    public function findByChecksum(string $checksum): ?Media;

    public function getTotalStorageSize(): int;

    /**
     * @return array<int, array{mimeGroup: string, totalSize: int, count: int}>
     */
    public function getStorageStatsByMimeGroup(): array;

    /**
     * @return array<int, array{id: string, originalFilename: string, size: int, mimeType: string}>
     */
    public function getLargestFiles(int $limit = 10): array;

    /**
     * @return string[]
     */
    public function getAllStoragePaths(): array;

    public function save(Media $media): void;

    public function delete(Media $media): void;
}
