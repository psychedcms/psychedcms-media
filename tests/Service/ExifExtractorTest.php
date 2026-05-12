<?php

declare(strict_types=1);

namespace PsychedCms\Media\Tests\Service;

use PHPUnit\Framework\TestCase;
use PsychedCms\Media\Service\ExifExtractor;

class ExifExtractorTest extends TestCase
{
    private ExifExtractor $extractor;

    protected function setUp(): void
    {
        $this->extractor = new ExifExtractor();
    }

    public function testExtractReturnsNullForUnsupportedMimeType(): void
    {
        $this->assertNull($this->extractor->extract('/tmp/whatever', 'image/png'));
        $this->assertNull($this->extractor->extract('/tmp/whatever', 'image/webp'));
    }

    public function testExtractReturnsNullWhenJpegHasNoUsableExifTags(): void
    {
        // Crop output from image-cropper packages strips every EXIF tag we care
        // about (camera info, exposure, GPS). The normalize() helper still
        // returned an empty $data array, which used to break the `: array`
        // signature with a fatal "must be array, null returned". This test
        // pins down the null path so it can never regress.
        $filePath = $this->createMinimalJpegWithoutExif();

        try {
            $this->assertNull($this->extractor->extract($filePath, 'image/jpeg'));
        } finally {
            @\unlink($filePath);
        }
    }

    public function testExtractReturnsArrayWhenJpegHasExifData(): void
    {
        if (!\function_exists('exif_read_data')) {
            $this->markTestSkipped('exif_read_data not available');
        }

        // Synthesise a valid JPEG with an EXIF Make/Model tag using GD + APP1.
        // We bypass that complexity by using a fixture file when available.
        $fixture = __DIR__ . '/../fixtures/with-exif.jpg';
        if (!\is_file($fixture)) {
            $this->markTestSkipped('with-exif.jpg fixture missing');
        }

        $result = $this->extractor->extract($fixture, 'image/jpeg');
        $this->assertIsArray($result);
    }

    /**
     * Create the smallest valid JPEG that GD can produce — pure pixels, no
     * EXIF block — and write it to a temp file.
     */
    private function createMinimalJpegWithoutExif(): string
    {
        if (!\function_exists('imagecreatetruecolor')) {
            $this->markTestSkipped('GD not available');
        }

        $img = \imagecreatetruecolor(1, 1);
        \assert($img !== false);
        $path = \tempnam(\sys_get_temp_dir(), 'exif-test-') . '.jpg';
        \imagejpeg($img, $path, 70);
        \imagedestroy($img);

        return $path;
    }
}
