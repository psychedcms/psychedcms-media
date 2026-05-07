<?php

declare(strict_types=1);

namespace PsychedCms\Media\Tests\Service;

use League\Flysystem\Config;
use League\Flysystem\DirectoryListing;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\UnableToReadFile;
use PHPUnit\Framework\TestCase;
use PsychedCms\Media\Entity\Media;
use PsychedCms\Media\Repository\MediaRepositoryInterface;
use PsychedCms\Media\Service\ExifExtractor;
use PsychedCms\Media\Service\FileValidator;
use PsychedCms\Media\Service\MediaService;
use PsychedCms\Media\Service\UploadPathResolver;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * End-to-end test of MediaService with in-memory fakes for storage & repository.
 *
 * Validates that two buffer uploads with the same content trigger exactly one
 * write to the storage and one entity persistence — i.e. the checksum dedup
 * actually short-circuits before MinIO is touched.
 */
class MediaServiceIntegrationTest extends TestCase
{
    public function testSameBufferUploadedTwiceWritesToStorageOnceAndPersistsOneMedia(): void
    {
        $storage = new InMemoryFilesystem();
        $repository = new InMemoryMediaRepository();

        $service = new MediaService(
            defaultStorage: $storage,
            mediaRepository: $repository,
            fileValidator: new FileValidator(),
            uploadPathResolver: new UploadPathResolver(),
            exifExtractor: new ExifExtractor(),
            httpClient: $this->createMock(HttpClientInterface::class),
        );

        $buffer = 'fake-jpeg-binary-payload';

        $first = $service->createFromBuffer(
            buffer: $buffer,
            directory: 'integration',
            filename: 'first.jpg',
            mimeType: 'image/jpeg',
        );

        $second = $service->createFromBuffer(
            buffer: $buffer,
            directory: 'integration',
            filename: 'second.jpg',
            mimeType: 'image/jpeg',
        );

        self::assertSame($first, $second, 'Second create should return the existing Media instance');
        self::assertSame(1, $repository->count(), 'Only one Media row should exist');
        self::assertSame(1, $storage->count(), 'Only one file should be written to storage');
        self::assertSame(\hash('sha256', $buffer), $first->getChecksum());
    }

    public function testDifferentBuffersResultInDistinctMediaEntities(): void
    {
        $storage = new InMemoryFilesystem();
        $repository = new InMemoryMediaRepository();

        $service = new MediaService(
            defaultStorage: $storage,
            mediaRepository: $repository,
            fileValidator: new FileValidator(),
            uploadPathResolver: new UploadPathResolver(),
            exifExtractor: new ExifExtractor(),
            httpClient: $this->createMock(HttpClientInterface::class),
        );

        $service->createFromBuffer('content-A', 'integration', 'a.jpg', 'image/jpeg');
        $service->createFromBuffer('content-B', 'integration', 'b.jpg', 'image/jpeg');

        self::assertSame(2, $repository->count());
        self::assertSame(2, $storage->count());
    }

    public function testDeleteRemovesBothBlobAndEntity(): void
    {
        $storage = new InMemoryFilesystem();
        $repository = new InMemoryMediaRepository();

        $service = new MediaService(
            defaultStorage: $storage,
            mediaRepository: $repository,
            fileValidator: new FileValidator(),
            uploadPathResolver: new UploadPathResolver(),
            exifExtractor: new ExifExtractor(),
            httpClient: $this->createMock(HttpClientInterface::class),
        );

        $media = $service->createFromBuffer('to-delete', 'integration', 'doomed.jpg', 'image/jpeg');
        self::assertSame(1, $storage->count());
        self::assertSame(1, $repository->count());

        $service->delete($media);

        self::assertSame(0, $storage->count());
        self::assertSame(0, $repository->count());
    }
}

/**
 * Minimal in-memory FilesystemOperator that supports the operations exercised by MediaService.
 */
class InMemoryFilesystem implements FilesystemOperator
{
    /** @var array<string, string> */
    private array $files = [];

    public function count(): int
    {
        return \count($this->files);
    }

    public function fileExists(string $location): bool
    {
        return isset($this->files[$location]);
    }

    public function directoryExists(string $location): bool
    {
        foreach (array_keys($this->files) as $path) {
            if (str_starts_with($path, rtrim($location, '/') . '/')) {
                return true;
            }
        }

        return false;
    }

    public function has(string $location): bool
    {
        return $this->fileExists($location);
    }

    public function write(string $location, string $contents, array $config = []): void
    {
        $this->files[$location] = $contents;
    }

    public function writeStream(string $location, $contents, array $config = []): void
    {
        $data = \is_resource($contents) ? \stream_get_contents($contents) : '';
        $this->files[$location] = \is_string($data) ? $data : '';
    }

    public function read(string $location): string
    {
        if (!isset($this->files[$location])) {
            throw UnableToReadFile::fromLocation($location);
        }

        return $this->files[$location];
    }

    public function readStream(string $location)
    {
        $contents = $this->read($location);
        $stream = \fopen('php://memory', 'r+');
        \fwrite($stream, $contents);
        \rewind($stream);

        return $stream;
    }

    public function delete(string $location): void
    {
        unset($this->files[$location]);
    }

    public function deleteDirectory(string $location): void
    {
        $prefix = rtrim($location, '/') . '/';
        foreach (array_keys($this->files) as $path) {
            if (str_starts_with($path, $prefix)) {
                unset($this->files[$path]);
            }
        }
    }

    public function createDirectory(string $location, array $config = []): void {}

    public function listContents(string $location, bool $deep = false): DirectoryListing
    {
        return new DirectoryListing([]);
    }

    public function move(string $source, string $destination, array $config = []): void
    {
        $this->files[$destination] = $this->read($source);
        $this->delete($source);
    }

    public function copy(string $source, string $destination, array $config = []): void
    {
        $this->files[$destination] = $this->read($source);
    }

    public function lastModified(string $location): int { return time(); }
    public function fileSize(string $location): int { return \strlen($this->read($location)); }
    public function mimeType(string $location): string { return 'application/octet-stream'; }
    public function setVisibility(string $path, string $visibility): void {}
    public function visibility(string $path): string { return Config::OPTION_VISIBILITY; }
    public function publicUrl(string $path, array $config = []): string { return '/' . $path; }

    public function temporaryUrl(string $path, \DateTimeInterface $expiresAt, array $config = []): string
    {
        return '/' . $path;
    }

    public function checksum(string $path, array $config = []): string
    {
        return \hash('sha256', $this->read($path));
    }
}

class InMemoryMediaRepository implements MediaRepositoryInterface
{
    /** @var array<string, Media> indexed by checksum */
    private array $byChecksum = [];

    public function count(): int
    {
        return \count($this->byChecksum);
    }

    public function findByMimeType(string $mimeType): iterable
    {
        return [];
    }

    public function findByChecksum(string $checksum): ?Media
    {
        return $this->byChecksum[$checksum] ?? null;
    }

    public function getTotalStorageSize(): int
    {
        return 0;
    }

    public function getStorageStatsByMimeGroup(): array
    {
        return [];
    }

    public function getLargestFiles(int $limit = 10): array
    {
        return [];
    }

    public function getAllStoragePaths(): array
    {
        return array_map(static fn (Media $m): string => $m->getStoragePath() ?? '', $this->byChecksum);
    }

    public function save(Media $media): void
    {
        $checksum = $media->getChecksum();
        if ($checksum !== null) {
            $this->byChecksum[$checksum] = $media;
        }
    }

    public function delete(Media $media): void
    {
        $checksum = $media->getChecksum();
        if ($checksum !== null) {
            unset($this->byChecksum[$checksum]);
        }
    }
}
