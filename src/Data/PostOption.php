<?php

declare(strict_types=1);

namespace Hei\SocialBu\Data;

/**
 * A single publish option supported by an account, as reported by
 * GET /posts/supported-options.
 */
final readonly class PostOption
{
    public const TYPE_STRING = 'string';

    public const TYPE_BOOLEAN = 'boolean';

    public const TYPE_DROPDOWN = 'dropdown';

    public const TYPE_JSON = 'json';

    /**
     * @param  string  $key  The key to send inside a post's `options` payload.
     * @param  array<array{value: string, label: string}>  $choices  Available values for dropdown options.
     */
    public function __construct(
        public string $key,
        public string $label,
        public string $type,
        public string $storage,
        public bool $required,
        public mixed $defaultValue,
        public ?int $maxLength,
        public array $choices,
    ) {}

    public static function fromArray(string $key, array $data): self
    {
        $choices = array_values(array_map(
            fn (array $choice) => [
                'value' => (string) ($choice['value'] ?? ''),
                'label' => (string) ($choice['label'] ?? ''),
            ],
            array_filter($data['options'] ?? [], 'is_array')
        ));

        $maxLength = $data['max_length'] ?? $data['maxLength'] ?? null;

        return new self(
            key: $key,
            label: $data['label'] ?? $key,
            type: $data['type'] ?? self::TYPE_STRING,
            storage: $data['storage'] ?? 'scalar',
            required: (bool) ($data['required'] ?? false),
            defaultValue: $data['default'] ?? null,
            maxLength: $maxLength !== null ? (int) $maxLength : null,
            choices: $choices,
        );
    }

    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'label' => $this->label,
            'type' => $this->type,
            'storage' => $this->storage,
            'required' => $this->required,
            'default' => $this->defaultValue,
            'max_length' => $this->maxLength,
            'options' => $this->choices,
        ];
    }

    public function isDropdown(): bool
    {
        return $this->type === self::TYPE_DROPDOWN;
    }

    public function isBoolean(): bool
    {
        return $this->type === self::TYPE_BOOLEAN;
    }

    /**
     * Whether the option value is uploaded rather than sent inline.
     */
    public function isFile(): bool
    {
        return $this->storage === 'file';
    }

    /**
     * The accepted values for a dropdown option.
     *
     * @return array<string>
     */
    public function choiceValues(): array
    {
        return array_column($this->choices, 'value');
    }

    /**
     * Whether a value is acceptable for this option.
     *
     * Only dropdowns and length-limited strings can be checked locally; any
     * other option accepts whatever the platform accepts, so this returns true.
     */
    public function accepts(mixed $value): bool
    {
        if ($this->isDropdown() && $this->choices !== []) {
            return in_array((string) $value, $this->choiceValues(), true);
        }

        if ($this->maxLength !== null && is_string($value)) {
            return mb_strlen($value) <= $this->maxLength;
        }

        return true;
    }
}
