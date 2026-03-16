<?php

declare(strict_types=1);

namespace PsychedCms\Media\Service;

interface OrphanedFileDetectorInterface
{
    /**
     * @return string[] List of storage paths not referenced by any Media entity
     */
    public function detect(): array;
}
