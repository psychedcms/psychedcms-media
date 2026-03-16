<?php

declare(strict_types=1);

namespace PsychedCms\Media\Service;

use League\Flysystem\FilesystemOperator;
use Spatie\ImageOptimizer\OptimizerChainFactory;

class ImageOptimizer implements ImageOptimizerInterface
{
    private const OPTIMIZABLE_TYPES = [
        'image/jpeg',
        'image/png',
        'image/gif',
    ];

    public function __construct(
        private readonly FilesystemOperator $defaultStorage,
        private readonly bool $enableWebp = true,
        private readonly bool $enableAvif = false,
        private readonly int $quality = 85,
    ) {
    }

    public function optimize(string $localPath, string $storagePath, string $mimeType): ?array
    {
        if (!\in_array($mimeType, self::OPTIMIZABLE_TYPES, true)) {
            return null;
        }

        $variants = [];

        // Optimize original with spatie/image-optimizer
        try {
            $optimizerChain = OptimizerChainFactory::create();
            $optimizerChain->optimize($localPath);
        } catch (\Throwable) {
            // Optimization is best-effort; continue
        }

        // Generate WebP variant
        if ($this->enableWebp && \function_exists('imagewebp')) {
            $webpVariant = $this->generateVariant($localPath, $storagePath, $mimeType, 'webp');
            if ($webpVariant !== null) {
                $variants['webp'] = $webpVariant;
            }
        }

        // Generate AVIF variant
        if ($this->enableAvif && \function_exists('imageavif')) {
            $avifVariant = $this->generateVariant($localPath, $storagePath, $mimeType, 'avif');
            if ($avifVariant !== null) {
                $variants['avif'] = $avifVariant;
            }
        }

        return $variants !== [] ? $variants : null;
    }

    /**
     * @return array{storagePath: string, mimeType: string, size: int}|null
     */
    private function generateVariant(string $localPath, string $originalStoragePath, string $mimeType, string $format): ?array
    {
        try {
            $image = match ($mimeType) {
                'image/jpeg' => imagecreatefromjpeg($localPath),
                'image/png' => imagecreatefrompng($localPath),
                'image/gif' => imagecreatefromgif($localPath),
                default => false,
            };

            if ($image === false) {
                return null;
            }

            $tempPath = sys_get_temp_dir() . '/' . uniqid('media_variant_', true) . '.' . $format;

            $success = match ($format) {
                'webp' => imagewebp($image, $tempPath, $this->quality),
                'avif' => imageavif($image, $tempPath, $this->quality),
                default => false,
            };

            imagedestroy($image);

            if (!$success || !file_exists($tempPath)) {
                return null;
            }

            $variantStoragePath = preg_replace('/\.[^.]+$/', '.' . $format, $originalStoragePath);
            $variantMimeType = 'image/' . $format;

            $stream = fopen($tempPath, 'r');
            $this->defaultStorage->writeStream($variantStoragePath, $stream, [
                'visibility' => 'public',
            ]);
            if (\is_resource($stream)) {
                fclose($stream);
            }

            $size = filesize($tempPath);
            @unlink($tempPath);

            return [
                'storagePath' => $variantStoragePath,
                'mimeType' => $variantMimeType,
                'size' => (int) $size,
            ];
        } catch (\Throwable) {
            return null;
        }
    }
}
