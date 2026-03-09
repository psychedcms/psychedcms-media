<?php

declare(strict_types=1);

namespace PsychedCms\Media\Attribute;

use Attribute;
use PsychedCms\Core\Attribute\Field\FieldAttribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
final class ImageField extends FieldAttribute
{
    public function __construct(
        public readonly ?int $maxSize = null,
        public readonly ?array $allowedTypes = null,
        public readonly int $thumbnailWidth = 400,
        public readonly int $thumbnailHeight = 400,
        ?string $label = null,
        ?string $group = null,
        ?string $placeholder = null,
        ?string $info = null,
        ?string $prefix = null,
        ?string $postfix = null,
        bool $separator = false,
        ?string $class = null,
        mixed $default = null,
        bool $required = false,
        bool $readonly = false,
        ?string $pattern = null,
        bool $index = false,
        bool $searchable = false,
        bool $translatable = false,
        bool $sanitise = true,
        ?bool $allowHtml = null,
    ) {
        parent::__construct(
            label: $label,
            group: $group,
            placeholder: $placeholder,
            info: $info,
            prefix: $prefix,
            postfix: $postfix,
            separator: $separator,
            class: $class,
            default: $default,
            required: $required,
            readonly: $readonly,
            pattern: $pattern,
            index: $index,
            searchable: $searchable,
            translatable: $translatable,
            sanitise: $sanitise,
            allowHtml: $allowHtml,
        );
    }

    public function getFieldType(): string
    {
        return 'image';
    }

    /**
     * @return array<string, mixed>
     */
    public function toSchemaArray(): array
    {
        $schema = parent::toSchemaArray();

        if ($this->maxSize !== null) {
            $schema['maxSize'] = $this->maxSize;
        }

        if ($this->allowedTypes !== null) {
            $schema['allowedTypes'] = $this->allowedTypes;
        }

        if ($this->thumbnailWidth !== 400) {
            $schema['thumbnailWidth'] = $this->thumbnailWidth;
        }

        if ($this->thumbnailHeight !== 400) {
            $schema['thumbnailHeight'] = $this->thumbnailHeight;
        }

        return $schema;
    }
}
