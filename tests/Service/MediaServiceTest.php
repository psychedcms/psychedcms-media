<?php

declare(strict_types=1);

namespace PsychedCms\Media\Tests\Service;

use League\Flysystem\FilesystemOperator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use PsychedCms\Media\Dto\MediaCreateInput;
use PsychedCms\Media\Entity\Media;
use PsychedCms\Media\Repository\MediaRepositoryInterface;
use PsychedCms\Media\Service\ExifExtractorInterface;
use PsychedCms\Media\Service\FileValidatorInterface;
use PsychedCms\Media\Service\MediaService;
use PsychedCms\Media\Service\UploadPathResolverInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class MediaServiceTest extends TestCase
{
    private FilesystemOperator&MockObject $storage;
    private MediaRepositoryInterface&MockObject $repository;
    private FileValidatorInterface&MockObject $validator;
    private UploadPathResolverInterface&MockObject $pathResolver;
    private ExifExtractorInterface&MockObject $exifExtractor;
    private HttpClientInterface&MockObject $httpClient;
    private MediaService $service;

    protected function setUp(): void
    {
        $this->storage = $this->createMock(FilesystemOperator::class);
        $this->repository = $this->createMock(MediaRepositoryInterface::class);
        $this->validator = $this->createMock(FileValidatorInterface::class);
        $this->pathResolver = $this->createMock(UploadPathResolverInterface::class);
        $this->exifExtractor = $this->createMock(ExifExtractorInterface::class);
        $this->httpClient = $this->createMock(HttpClientInterface::class);

        $this->pathResolver
            ->method('sanitizeFilename')
            ->willReturnCallback(fn (string $name): string => 'safe-' . $name);
        $this->pathResolver
            ->method('resolve')
            ->willReturnCallback(fn (?string $dir): string => ($dir ?? 'uploads') . '/2026/05/');

        $this->service = new MediaService(
            defaultStorage: $this->storage,
            mediaRepository: $this->repository,
            fileValidator: $this->validator,
            uploadPathResolver: $this->pathResolver,
            exifExtractor: $this->exifExtractor,
            httpClient: $this->httpClient,
        );
    }

    public function testCreateWritesAndPersistsWhenNoChecksumMatch(): void
    {
        $buffer = 'fake-image-bytes';
        $expectedChecksum = \hash('sha256', $buffer);

        $this->validator->expects(self::once())->method('validateBuffer')->with($buffer, 'image/jpeg', false);
        $this->repository->expects(self::once())->method('findByChecksum')->with($expectedChecksum)->willReturn(null);
        $this->storage->expects(self::once())->method('write')->with(
            'fixtures/2026/05/safe-cover.jpg',
            $buffer,
            ['visibility' => 'public'],
        );
        $this->repository->expects(self::once())->method('save')->with(self::isInstanceOf(Media::class));

        $media = $this->service->create(new MediaCreateInput(
            buffer: $buffer,
            directory: 'fixtures',
            filename: 'cover.jpg',
            mimeType: 'image/jpeg',
            altText: 'cover art',
            credits: 'photographer x',
            title: 'My title',
            description: 'My description',
        ));

        self::assertSame('safe-cover.jpg', $media->getFilename());
        self::assertSame('cover.jpg', $media->getOriginalFilename());
        self::assertSame('image/jpeg', $media->getMimeType());
        self::assertSame(\strlen($buffer), $media->getSize());
        self::assertSame('fixtures/2026/05/safe-cover.jpg', $media->getStoragePath());
        self::assertSame($expectedChecksum, $media->getChecksum());
        self::assertSame('cover art', $media->getAltText());
        self::assertSame('photographer x', $media->getCredits());
        self::assertSame('My title', $media->getTitle());
        self::assertSame('My description', $media->getDescription());
    }

    public function testCreateReturnsExistingWhenChecksumMatchesAndDoesNotWrite(): void
    {
        $buffer = 'duplicate-bytes';
        $existing = new Media();

        $this->validator->expects(self::once())->method('validateBuffer');
        $this->repository->expects(self::once())->method('findByChecksum')->willReturn($existing);
        $this->storage->expects(self::never())->method('write');
        $this->repository->expects(self::never())->method('save');

        $result = $this->service->create(new MediaCreateInput(
            buffer: $buffer,
            directory: 'any',
            filename: 'dup.jpg',
            mimeType: 'image/jpeg',
        ));

        self::assertSame($existing, $result);
    }

    public function testCreateRunsQuotaCheckOnlyWhenRequested(): void
    {
        $buffer = 'bytes';

        $this->validator->expects(self::once())->method('validateBuffer');
        $this->validator->expects(self::never())->method('validateQuota');
        $this->repository->expects(self::once())->method('findByChecksum')->willReturn(null);

        $this->service->create(new MediaCreateInput(
            buffer: $buffer,
            directory: 'd',
            filename: 'f.jpg',
            mimeType: 'image/jpeg',
            checkQuota: false,
        ));
    }

    public function testCreateFromBase64DecodesAndConvergesToCreate(): void
    {
        $original = 'binary-content';
        $base64 = \base64_encode($original);
        $expectedChecksum = \hash('sha256', $original);

        $this->validator->expects(self::once())
            ->method('validateBuffer')
            ->with($original, 'image/png');
        $this->repository->expects(self::once())
            ->method('findByChecksum')
            ->with($expectedChecksum)
            ->willReturn(null);
        $this->storage->expects(self::once())->method('write');
        $this->repository->expects(self::once())->method('save');

        $media = $this->service->createFromBase64(
            base64: $base64,
            directory: 'b64',
            filename: 'pic.png',
            mimeTypeHint: 'image/png',
        );

        self::assertSame('image/png', $media->getMimeType());
    }

    public function testCreateFromUrlDownloadsAndConvergesToCreate(): void
    {
        $url = 'https://example.com/foo.jpg';
        $payload = 'remote-bytes';
        $expectedChecksum = \hash('sha256', $payload);

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(200);
        $response->method('getContent')->willReturn($payload);
        $response->method('getHeaders')->willReturn(['content-type' => ['image/jpeg']]);

        $this->httpClient->expects(self::once())
            ->method('request')
            ->with('GET', $url, self::anything())
            ->willReturn($response);

        $this->validator->expects(self::once())->method('validateBuffer')->with($payload, 'image/jpeg');
        $this->repository->expects(self::once())->method('findByChecksum')->with($expectedChecksum)->willReturn(null);
        $this->storage->expects(self::once())->method('write');
        $this->repository->expects(self::once())->method('save');

        $media = $this->service->createFromUrl(
            url: $url,
            directory: 'remote',
            altText: 'remote alt',
        );

        self::assertSame('foo.jpg', $media->getOriginalFilename());
        self::assertSame('remote alt', $media->getAltText());
    }

    public function testCreateFromUrlThrowsOnNon200(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(404);
        $this->httpClient->method('request')->willReturn($response);

        $this->expectException(\RuntimeException::class);
        $this->service->createFromUrl(url: 'https://example.com/missing.jpg', directory: 'd');
    }

    public function testDeleteRemovesBlobAndVariantsAndEntity(): void
    {
        $media = new Media();
        $media->setStoragePath('uploads/main.jpg');
        $media->setOptimizedVariants([
            ['storagePath' => 'uploads/main-thumb.webp'],
            ['storagePath' => 'uploads/main-large.webp'],
        ]);

        $this->storage->method('fileExists')->willReturn(true);
        $this->storage->expects(self::exactly(3))->method('delete');
        $this->repository->expects(self::once())->method('delete')->with($media);

        $this->service->delete($media);
    }

    public function testDeleteSkipsMissingFiles(): void
    {
        $media = new Media();
        $media->setStoragePath('uploads/missing.jpg');

        $this->storage->method('fileExists')->willReturn(false);
        $this->storage->expects(self::never())->method('delete');
        $this->repository->expects(self::once())->method('delete');

        $this->service->delete($media);
    }
}
