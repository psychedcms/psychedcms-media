<?php

declare(strict_types=1);

namespace PsychedCms\Media\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use Doctrine\ORM\Mapping as ORM;
use PsychedCms\Media\Repository\MediaCategoryRepository;

#[ORM\Entity(repositoryClass: MediaCategoryRepository::class)]
#[ORM\Table(name: 'media_categories')]
#[ApiResource(
    operations: [
        new GetCollection(),
        new Get(),
        new Post(),
        new Patch(),
        new Delete(),
    ],
    normalizationContext: ['groups' => ['media_category:read']],
    denormalizationContext: ['groups' => ['media_category:write']],
)]
class MediaCategory implements MediaCategoryInterface
{
    use MediaCategoryTrait;
}
