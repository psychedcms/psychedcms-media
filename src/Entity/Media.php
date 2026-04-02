<?php

declare(strict_types=1);

namespace PsychedCms\Media\Entity;

use ApiPlatform\Doctrine\Orm\Filter\DateFilter;
use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\RangeFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use Doctrine\ORM\Mapping as ORM;
use PsychedCms\Media\Dto\BulkCategorizeInput;
use PsychedCms\Media\Dto\BulkDeleteInput;
use PsychedCms\Media\Dto\BulkUpdateInput;
use PsychedCms\Media\Dto\MediaReplaceInput;
use PsychedCms\Media\Repository\MediaRepository;
use PsychedCms\Media\State\MediaBulkCategorizeProcessor;
use PsychedCms\Media\State\MediaBulkDeleteProcessor;
use PsychedCms\Media\State\MediaBulkUpdateProcessor;
use PsychedCms\Media\State\MediaDeleteProcessor;
use PsychedCms\Media\State\MediaReplaceProcessor;
use PsychedCms\Media\State\MediaUploadProcessor;

#[ORM\Entity(repositoryClass: MediaRepository::class)]
#[ORM\Table(name: 'media')]
#[ORM\Index(columns: ['mime_type'], name: 'idx_media_mime_type')]
#[ORM\Index(columns: ['created_at'], name: 'idx_media_created_at')]
#[ORM\Index(columns: ['checksum'], name: 'idx_media_checksum')]
#[ORM\Index(columns: ['mime_type', 'created_at'], name: 'idx_media_mime_type_created_at')]
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
        new Post(
            uriTemplate: '/media/{id}/replace',
            inputFormats: ['multipart' => ['multipart/form-data']],
            deserialize: false,
            input: MediaReplaceInput::class,
            processor: MediaReplaceProcessor::class,
            name: 'media_replace',
        ),
        new Post(
            uriTemplate: '/media/bulk-delete',
            inputFormats: ['json' => ['application/json'], 'jsonld' => ['application/ld+json']],
            denormalizationContext: [],
            input: BulkDeleteInput::class,
            output: false,
            status: 204,
            processor: MediaBulkDeleteProcessor::class,
            name: 'media_bulk_delete',
        ),
        new Post(
            uriTemplate: '/media/bulk-categorize',
            inputFormats: ['json' => ['application/json'], 'jsonld' => ['application/ld+json']],
            denormalizationContext: [],
            input: BulkCategorizeInput::class,
            output: false,
            status: 204,
            processor: MediaBulkCategorizeProcessor::class,
            name: 'media_bulk_categorize',
        ),
        new Post(
            uriTemplate: '/media/bulk-update',
            inputFormats: ['json' => ['application/json'], 'jsonld' => ['application/ld+json']],
            denormalizationContext: [],
            input: BulkUpdateInput::class,
            output: false,
            status: 204,
            processor: MediaBulkUpdateProcessor::class,
            name: 'media_bulk_update',
        ),
    ],
    normalizationContext: ['groups' => ['media:read']],
    denormalizationContext: ['groups' => ['media:write']],
)]
#[ApiFilter(SearchFilter::class, properties: [
    'originalFilename' => 'ipartial',
    'title' => 'ipartial',
    'altText' => 'ipartial',
    'description' => 'ipartial',
    'mimeType' => 'start',
    'storage' => 'exact',
    'storagePath' => 'start',
])]
#[ApiFilter(DateFilter::class, properties: ['createdAt'])]
#[ApiFilter(RangeFilter::class, properties: ['size', 'width', 'height'])]
#[ApiFilter(OrderFilter::class, properties: ['createdAt', 'originalFilename', 'size'])]
class Media implements MediaInterface
{
    use MediaTrait;
}
