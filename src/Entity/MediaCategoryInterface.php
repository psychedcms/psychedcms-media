<?php

declare(strict_types=1);

namespace PsychedCms\Media\Entity;

use DateTimeImmutable;
use Symfony\Component\Uid\Ulid;

interface MediaCategoryInterface
{
    public function getId(): ?Ulid;

    public function getName(): ?string;

    public function setName(string $name): static;

    public function getSlug(): ?string;

    public function setSlug(string $slug): static;

    public function getColor(): ?string;

    public function setColor(?string $color): static;

    public function getIcon(): ?string;

    public function setIcon(?string $icon): static;

    public function getCreatedAt(): ?DateTimeImmutable;

    public function getUpdatedAt(): ?DateTimeImmutable;
}
