<?php

declare(strict_types=1);

namespace PsychedCms\Media\Entity;

use DateTimeImmutable;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Uid\Ulid;

interface MediaInterface
{
    public function getId(): ?Ulid;

    public function getFilename(): ?string;

    public function setFilename(string $filename): static;

    public function getOriginalFilename(): ?string;

    public function setOriginalFilename(string $originalFilename): static;

    public function getMimeType(): ?string;

    public function setMimeType(string $mimeType): static;

    public function getSize(): ?int;

    public function setSize(int $size): static;

    public function getWidth(): ?int;

    public function setWidth(?int $width): static;

    public function getHeight(): ?int;

    public function setHeight(?int $height): static;

    public function getAltText(): ?string;

    public function setAltText(?string $altText): static;

    public function getTitle(): ?string;

    public function setTitle(?string $title): static;

    public function getDescription(): ?string;

    public function setDescription(?string $description): static;

    public function getStoragePath(): ?string;

    public function setStoragePath(string $storagePath): static;

    public function getChecksum(): ?string;

    public function setChecksum(?string $checksum): static;

    public function getExifData(): ?array;

    public function setExifData(?array $exifData): static;

    public function getOptimizedVariants(): ?array;

    public function setOptimizedVariants(?array $optimizedVariants): static;

    /**
     * @return Collection<int, MediaCategory>
     */
    public function getCategories(): Collection;

    public function addCategory(MediaCategory $category): static;

    public function removeCategory(MediaCategory $category): static;

    public function getCreatedAt(): ?DateTimeImmutable;

    public function getUpdatedAt(): ?DateTimeImmutable;
}
