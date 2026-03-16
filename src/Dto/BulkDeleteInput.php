<?php

declare(strict_types=1);

namespace PsychedCms\Media\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class BulkDeleteInput
{
    /**
     * @var string[]
     */
    #[Assert\NotBlank]
    #[Assert\Count(min: 1)]
    public array $ids = [];
}
