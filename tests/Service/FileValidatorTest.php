<?php

declare(strict_types=1);

namespace PsychedCms\Media\Tests\Service;

use PHPUnit\Framework\TestCase;
use PsychedCms\Media\Exception\FileSizeExceededException;
use PsychedCms\Media\Exception\InvalidFileTypeException;
use PsychedCms\Media\Service\FileValidator;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class FileValidatorTest extends TestCase
{
    private FileValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new FileValidator();
    }

    public function testAcceptsAllowedMimeType(): void
    {
        $file = $this->createUploadedFile('test.jpg', 'image/jpeg', 1024);
        $this->validator->validate($file);
        $this->addToAssertionCount(1);
    }

    public function testRejectsDisallowedMimeType(): void
    {
        $file = $this->createUploadedFile('test.exe', 'application/x-executable', 1024);
        $this->expectException(InvalidFileTypeException::class);
        $this->validator->validate($file);
    }

    public function testRejectsOversizedFile(): void
    {
        $validator = new FileValidator(maxSize: 1024);
        $file = $this->createUploadedFile('test.jpg', 'image/jpeg', 2048);
        $this->expectException(FileSizeExceededException::class);
        $validator->validate($file);
    }

    public function testAcceptsFileWithinSizeLimit(): void
    {
        $validator = new FileValidator(maxSize: 2048);
        $file = $this->createUploadedFile('test.jpg', 'image/jpeg', 1024);
        $validator->validate($file);
        $this->addToAssertionCount(1);
    }

    public function testRejectsSvgWithScript(): void
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'svg');
        file_put_contents($tmpFile, '<svg><script>alert("xss")</script></svg>');

        $file = new UploadedFile($tmpFile, 'test.svg', 'image/svg+xml', null, true);

        $this->expectException(InvalidFileTypeException::class);
        $this->expectExceptionMessage('dangerous content');
        $this->validator->validate($file);

        @unlink($tmpFile);
    }

    public function testAcceptsSafeSvg(): void
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'svg');
        file_put_contents($tmpFile, '<svg xmlns="http://www.w3.org/2000/svg"><rect width="100" height="100"/></svg>');

        $file = new UploadedFile($tmpFile, 'test.svg', 'image/svg+xml', null, true);
        $this->validator->validate($file);
        $this->addToAssertionCount(1);

        @unlink($tmpFile);
    }

    public function testCustomAllowedTypes(): void
    {
        $validator = new FileValidator(allowedTypes: ['text/plain']);
        $file = $this->createUploadedFile('test.jpg', 'image/jpeg', 1024);
        $this->expectException(InvalidFileTypeException::class);
        $validator->validate($file);
    }

    private function createUploadedFile(string $name, string $mimeType, int $size): UploadedFile
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'test');
        file_put_contents($tmpFile, str_repeat('x', $size));

        $file = $this->createMock(UploadedFile::class);
        $file->method('getMimeType')->willReturn($mimeType);
        $file->method('getClientMimeType')->willReturn($mimeType);
        $file->method('getSize')->willReturn($size);
        $file->method('getPathname')->willReturn($tmpFile);
        $file->method('getClientOriginalName')->willReturn($name);

        return $file;
    }
}
