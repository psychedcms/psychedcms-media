<?php

declare(strict_types=1);

namespace PsychedCms\Media\Service;

use PsychedCms\Media\Dto\MediaCreateInput;
use PsychedCms\Media\Entity\Media;

interface MediaServiceInterface
{
    /**
     * Persist a Media entity from an in-memory buffer.
     *
     * Algorithm:
     *  1. Validate the buffer (mime type, size).
     *  2. Compute SHA-256 checksum on the buffer.
     *  3. If a Media with the same checksum already exists, return it (no storage write).
     *  4. Otherwise: write to storage, build Media entity, persist, return.
     */
    public function create(MediaCreateInput $input): Media;

    /**
     * Download from URL then delegate to create().
     */
    public function createFromUrl(
        string $url,
        string $directory,
        ?string $altText = null,
        ?string $credits = null,
        ?string $title = null,
        ?string $description = null,
        ?string $storage = null,
    ): Media;

    /**
     * Decode base64 then delegate to create().
     */
    public function createFromBase64(
        string $base64,
        string $directory,
        string $filename,
        ?string $mimeTypeHint = null,
        ?string $altText = null,
        ?string $credits = null,
        ?string $title = null,
        ?string $description = null,
        ?string $storage = null,
    ): Media;

    /**
     * Direct buffer create — convenience for callers that already have raw bytes.
     */
    public function createFromBuffer(
        string $buffer,
        string $directory,
        string $filename,
        string $mimeType,
        ?string $altText = null,
        ?string $credits = null,
        ?string $title = null,
        ?string $description = null,
        ?string $storage = null,
        bool $skipSizeCheck = false,
        bool $checkQuota = false,
    ): Media;

    /**
     * Remove a Media: delete the underlying blob (and optimized variants) then the entity.
     */
    public function delete(Media $media): void;
}
