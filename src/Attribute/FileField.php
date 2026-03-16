<?php

declare(strict_types=1);

namespace PsychedCms\Media\Attribute;

use Attribute;
use PsychedCms\Core\Attribute\Field\FieldAttribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
final class FileField extends FieldAttribute
{
    public function __construct(
        public readonly ?int $maxSize = null,
        public readonly ?array $allowedTypes = null,
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
        ?bool $listColumn = null,
        ?int $listColumnOrder = null,
        ?string $listDisplayPattern = null,
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
            listColumn: $listColumn,
            listColumnOrder: $listColumnOrder,
            listDisplayPattern: $listDisplayPattern,
        );
    }

    public function getFieldType(): string
    {
        return 'file';
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

        return $schema;
    }
}
