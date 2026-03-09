<?php

declare(strict_types=1);

namespace PsychedCms\Media\Tests\Service;

use PHPUnit\Framework\TestCase;
use PsychedCms\Media\Service\UploadPathResolver;

class UploadPathResolverTest extends TestCase
{
    private UploadPathResolver $resolver;

    protected function setUp(): void
    {
        $this->resolver = new UploadPathResolver();
    }

    public function testResolveWithContentType(): void
    {
        $path = $this->resolver->resolve('posts');
        $now = new \DateTimeImmutable();

        $this->assertStringStartsWith('posts/', $path);
        $this->assertStringContainsString($now->format('Y'), $path);
        $this->assertStringContainsString($now->format('m'), $path);
        $this->assertStringEndsWith('/', $path);
    }

    public function testResolveWithoutContentType(): void
    {
        $path = $this->resolver->resolve();
        $this->assertStringStartsWith('uploads/', $path);
    }

    public function testSanitizeFilenamePreservesExtension(): void
    {
        $filename = $this->resolver->sanitizeFilename('My Photo (1).JPEG');

        $this->assertStringEndsWith('.jpeg', $filename);
        $this->assertMatchesRegularExpression('/^my-photo-1-[a-f0-9]{8}\.jpeg$/', $filename);
    }

    public function testSanitizeFilenameHandlesNoExtension(): void
    {
        $filename = $this->resolver->sanitizeFilename('README');

        $this->assertMatchesRegularExpression('/^readme-[a-f0-9]{8}$/', $filename);
    }

    public function testSanitizeFilenameProducesUniqueResults(): void
    {
        $name1 = $this->resolver->sanitizeFilename('test.jpg');
        $name2 = $this->resolver->sanitizeFilename('test.jpg');

        $this->assertNotEquals($name1, $name2);
    }

    public function testSanitizeFilenameHandlesSpecialCharacters(): void
    {
        $filename = $this->resolver->sanitizeFilename('Über Café — résumé.pdf');

        $this->assertStringEndsWith('.pdf', $filename);
        $this->assertDoesNotMatchRegularExpression('/[^a-z0-9.\-]/', $filename);
    }
}
