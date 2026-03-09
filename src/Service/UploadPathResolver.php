<?php

declare(strict_types=1);

namespace PsychedCms\Media\Service;

use Symfony\Component\String\Slugger\AsciiSlugger;

class UploadPathResolver implements UploadPathResolverInterface
{
    private readonly AsciiSlugger $slugger;

    public function __construct()
    {
        $this->slugger = new AsciiSlugger();
    }

    public function resolve(?string $contentType = null): string
    {
        $now = new \DateTimeImmutable();
        $prefix = $contentType ? strtolower($contentType) : 'uploads';

        return \sprintf('%s/%s/%s/', $prefix, $now->format('Y'), $now->format('m'));
    }

    public function sanitizeFilename(string $original): string
    {
        $extension = pathinfo($original, PATHINFO_EXTENSION);
        $basename = pathinfo($original, PATHINFO_FILENAME);

        $slugged = $this->slugger->slug($basename)->lower()->toString();

        if ($slugged === '') {
            $slugged = 'file';
        }

        $suffix = substr(bin2hex(random_bytes(4)), 0, 8);

        return $extension !== ''
            ? \sprintf('%s-%s.%s', $slugged, $suffix, strtolower($extension))
            : \sprintf('%s-%s', $slugged, $suffix);
    }
}
