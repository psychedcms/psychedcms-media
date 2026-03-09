<?php

declare(strict_types=1);

namespace PsychedCms\Media\Serializer;

use PsychedCms\Media\Entity\Media;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class MediaUrlNormalizer implements NormalizerInterface, NormalizerAwareInterface
{
    use NormalizerAwareTrait;

    private const ALREADY_CALLED = 'MEDIA_URL_NORMALIZER_ALREADY_CALLED';
    private const DEFAULT_THUMBNAIL_WIDTH = 400;
    private const DEFAULT_THUMBNAIL_HEIGHT = 400;

    public function __construct(
        private readonly ?string $imgproxyUrl = null,
        private readonly ?string $mediaPublicUrl = null,
    ) {
    }

    /**
     * @param Media $object
     * @return array<string, mixed>
     */
    public function normalize(mixed $object, ?string $format = null, array $context = []): array
    {
        $context[self::ALREADY_CALLED] = true;

        /** @var array<string, mixed> $data */
        $data = $this->normalizer->normalize($object, $format, $context);

        $storagePath = $object->getStoragePath();
        if ($storagePath === null) {
            return $data;
        }

        $data['url'] = $this->buildOriginalUrl($storagePath);
        $data['thumbnailUrl'] = $this->buildThumbnailUrl(
            $storagePath,
            self::DEFAULT_THUMBNAIL_WIDTH,
            self::DEFAULT_THUMBNAIL_HEIGHT,
        );

        return $data;
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        if (isset($context[self::ALREADY_CALLED])) {
            return false;
        }

        return $data instanceof Media;
    }

    /**
     * @return array<string, bool>
     */
    public function getSupportedTypes(?string $format): array
    {
        return [
            Media::class => false,
        ];
    }

    private function buildOriginalUrl(string $storagePath): string
    {
        if ($this->mediaPublicUrl !== null && $this->mediaPublicUrl !== '') {
            return rtrim($this->mediaPublicUrl, '/') . '/orig/' . ltrim($storagePath, '/');
        }

        if ($this->imgproxyUrl !== null && $this->imgproxyUrl !== '') {
            return rtrim($this->imgproxyUrl, '/') . '/orig/' . ltrim($storagePath, '/');
        }

        return '/media/' . ltrim($storagePath, '/');
    }

    private function buildThumbnailUrl(string $storagePath, int $width, int $height): string
    {
        if ($this->imgproxyUrl !== null && $this->imgproxyUrl !== '') {
            return \sprintf(
                '%s/%dx%d/%s',
                rtrim($this->imgproxyUrl, '/'),
                $width,
                $height,
                ltrim($storagePath, '/'),
            );
        }

        return $this->buildOriginalUrl($storagePath);
    }
}
