<?php

declare(strict_types=1);

namespace PsychedCms\Media\Entity;

use DateTimeImmutable;

interface MediaInterface
{
    public function getId(): ?int;

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

    public function getCreatedAt(): ?DateTimeImmutable;

    public function getUpdatedAt(): ?DateTimeImmutable;
}
