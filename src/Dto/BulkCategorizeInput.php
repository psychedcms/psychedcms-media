<?php

declare(strict_types=1);

namespace PsychedCms\Media\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class BulkCategorizeInput
{
    /**
     * @var string[]
     */
    #[Assert\NotBlank]
    #[Assert\Count(min: 1)]
    public array $mediaIds = [];

    /**
     * @var string[]
     */
    #[Assert\NotBlank]
    #[Assert\Count(min: 1)]
    public array $categoryIds = [];

    public string $action = 'add'; // 'add' or 'remove'
}
