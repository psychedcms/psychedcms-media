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
use PsychedCms\Media\Repository\MediaRepository;
use PsychedCms\Media\State\MediaDeleteProcessor;
use PsychedCms\Media\State\MediaUploadProcessor;

#[ORM\Entity(repositoryClass: MediaRepository::class)]
#[ORM\Table(name: 'media')]
#[ORM\Index(columns: ['mime_type'], name: 'idx_media_mime_type')]
#[ORM\Index(columns: ['created_at'], name: 'idx_media_created_at')]
#[ApiResource(
    operations: [
        new GetCollection(),
        new Get(),
        new Post(
            inputFormats: ['multipart' => ['multipart/form-data']],
            deserialize: false,
            processor: MediaUploadProcessor::class,
        ),
        new Patch(),
        new Delete(processor: MediaDeleteProcessor::class),
    ],
    normalizationContext: ['groups' => ['media:read']],
    denormalizationContext: ['groups' => ['media:write']],
)]
class Media implements MediaInterface
{
    use MediaTrait;
}
