<?php

declare(strict_types=1);

namespace PsychedCms\Media\Serializer;

use PsychedCms\Media\Entity\Media;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class ImageFieldNormalizer implements NormalizerInterface, NormalizerAwareInterface
{
    use NormalizerAwareTrait;

    private const ALREADY_CALLED = 'IMAGE_FIELD_NORMALIZER_ALREADY_CALLED';

    public function __construct(
        private readonly ?string $mediaPublicUrl = null,
        private readonly string $bucket = 'hilo-media',
    ) {
    }

    /**
     * @return array<string, mixed>|string|int|float|bool|\ArrayObject<int|string, mixed>|null
     */
    public function normalize(mixed $object, ?string $format = null, array $context = []): array|string|int|float|bool|\ArrayObject|null
    {
        $context[self::ALREADY_CALLED] = true;

        $data = $this->normalizer->normalize($object, $format, $context);

        if (\is_array($data)) {
            $this->enrichImageFields($data);
        }

        return $data;
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        if (isset($context[self::ALREADY_CALLED])) {
            return false;
        }

        return \is_object($data) && !$data instanceof Media;
    }

    /**
     * @return array<string, bool>
     */
    public function getSupportedTypes(?string $format): array
    {
        return ['object' => false];
    }

    /**
     * @param array<string, mixed> $data
     */
    private function enrichImageFields(array &$data): void
    {
        foreach ($data as &$value) {
            if (!\is_array($value)) {
                continue;
            }

            if (isset($value['storagePath']) && \is_string($value['storagePath']) && $value['storagePath'] !== '') {
                $value['url'] = $this->buildUrl($value['storagePath']);
            } else {
                $this->enrichImageFields($value);
            }
        }
    }

    private function buildUrl(string $storagePath): string
    {
        if ($this->mediaPublicUrl !== null && $this->mediaPublicUrl !== '') {
            return \rtrim($this->mediaPublicUrl, '/') . '/' . $this->bucket . '/' . \ltrim($storagePath, '/');
        }

        return '/media/' . \ltrim($storagePath, '/');
    }
}
