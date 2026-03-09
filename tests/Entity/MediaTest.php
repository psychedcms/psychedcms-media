<?php

declare(strict_types=1);

namespace PsychedCms\Media\Tests\Entity;

use PHPUnit\Framework\TestCase;
use PsychedCms\Media\Entity\Media;
use PsychedCms\Media\Entity\MediaInterface;

class MediaTest extends TestCase
{
    public function testImplementsInterface(): void
    {
        $media = new Media();
        $this->assertInstanceOf(MediaInterface::class, $media);
    }

    public function testGettersAndSetters(): void
    {
        $media = new Media();

        $media->setFilename('test-abc12345.jpg');
        $this->assertSame('test-abc12345.jpg', $media->getFilename());

        $media->setOriginalFilename('My Photo.jpg');
        $this->assertSame('My Photo.jpg', $media->getOriginalFilename());

        $media->setMimeType('image/jpeg');
        $this->assertSame('image/jpeg', $media->getMimeType());

        $media->setSize(123456);
        $this->assertSame(123456, $media->getSize());

        $media->setWidth(1920);
        $this->assertSame(1920, $media->getWidth());

        $media->setHeight(1080);
        $this->assertSame(1080, $media->getHeight());

        $media->setAltText('A test image');
        $this->assertSame('A test image', $media->getAltText());

        $media->setTitle('Test Image');
        $this->assertSame('Test Image', $media->getTitle());

        $media->setDescription('A description');
        $this->assertSame('A description', $media->getDescription());

        $media->setStoragePath('uploads/2026/03/test.jpg');
        $this->assertSame('uploads/2026/03/test.jpg', $media->getStoragePath());
    }

    public function testNullableFields(): void
    {
        $media = new Media();

        $this->assertNull($media->getId());
        $this->assertNull($media->getWidth());
        $this->assertNull($media->getHeight());
        $this->assertNull($media->getAltText());
        $this->assertNull($media->getTitle());
        $this->assertNull($media->getDescription());
        $this->assertNull($media->getCreatedAt());
        $this->assertNull($media->getUpdatedAt());
    }

    public function testSetWidthToNull(): void
    {
        $media = new Media();
        $media->setWidth(100);
        $media->setWidth(null);
        $this->assertNull($media->getWidth());
    }

    public function testFluentInterface(): void
    {
        $media = new Media();

        $result = $media
            ->setFilename('test.jpg')
            ->setOriginalFilename('original.jpg')
            ->setMimeType('image/jpeg')
            ->setSize(1024)
            ->setStoragePath('uploads/test.jpg');

        $this->assertSame($media, $result);
    }
}
