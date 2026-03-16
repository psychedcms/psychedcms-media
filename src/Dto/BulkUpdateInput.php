<?php

declare(strict_types=1);

namespace PsychedCms\Media\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class BulkUpdateInput
{
    /**
     * @var string[]
     */
    #[Assert\NotBlank]
    #[Assert\Count(min: 1)]
    public array $ids = [];

    public ?string $altText = null;

    public ?string $title = null;

    public ?string $description = null;
}
