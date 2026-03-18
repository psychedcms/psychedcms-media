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

    /** @param array<string, string> $storagePublicUrls Map of storage name → public base URL */
    public function __construct(
        private readonly ?string $imgproxyUrl = null,
        private readonly ?string $mediaPublicUrl = null,
        private readonly array $storagePublicUrls = [],
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

        $storage = $object->getStorage();
        $data['url'] = $this->buildOriginalUrl($storagePath, $storage);
        $data['thumbnailUrl'] = $this->buildThumbnailUrl(
            $storagePath,
            self::DEFAULT_THUMBNAIL_WIDTH,
            self::DEFAULT_THUMBNAIL_HEIGHT,
            $storage,
        );

        // Add variant URLs
        $variants = $object->getOptimizedVariants();
        if ($variants !== null) {
            $variantUrls = [];
            foreach ($variants as $variantFormat => $variant) {
                $variantPath = $variant['storagePath'] ?? null;
                if ($variantPath !== null) {
                    $variantUrls[$variantFormat] = [
                        'url' => $this->buildOriginalUrl($variantPath, $storage),
                        'mimeType' => $variant['mimeType'] ?? null,
                        'size' => $variant['size'] ?? null,
                    ];
                }
            }
            if ($variantUrls !== []) {
                $data['variants'] = $variantUrls;
            }
        }

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

    private function resolvePublicUrl(string $storage): ?string
    {
        if (isset($this->storagePublicUrls[$storage]) && $this->storagePublicUrls[$storage] !== '') {
            return $this->storagePublicUrls[$storage];
        }

        return $this->mediaPublicUrl;
    }

    private function buildOriginalUrl(string $storagePath, string $storage = 'content'): string
    {
        if ($this->imgproxyUrl !== null && $this->imgproxyUrl !== '') {
            return \sprintf(
                '%s/%s/orig/%s',
                rtrim($this->imgproxyUrl, '/'),
                $storage,
                ltrim($storagePath, '/'),
            );
        }

        $publicUrl = $this->resolvePublicUrl($storage);
        if ($publicUrl !== null && $publicUrl !== '') {
            return rtrim($publicUrl, '/') . '/' . ltrim($storagePath, '/');
        }

        return '/media/' . ltrim($storagePath, '/');
    }

    private function buildThumbnailUrl(string $storagePath, int $width, int $height, string $storage = 'content'): string
    {
        if ($this->imgproxyUrl !== null && $this->imgproxyUrl !== '') {
            return \sprintf(
                '%s/%s/%dx%d/%s',
                rtrim($this->imgproxyUrl, '/'),
                $storage,
                $width,
                $height,
                ltrim($storagePath, '/'),
            );
        }

        return $this->buildOriginalUrl($storagePath, $storage);
    }
}
