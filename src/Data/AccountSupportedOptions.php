<?php

declare(strict_types=1);

namespace Hei\SocialBu\Data;

/**
 * The publish options one account supports, as reported by
 * GET /posts/supported-options.
 */
final readonly class AccountSupportedOptions
{
    /**
     * @param  array<string, PostOption>  $options  Keyed by option key.
     */
    public function __construct(
        public int $accountId,
        public string $accountType,
        public string $accountName,
        public array $options,
    ) {}

    public static function fromArray(array $data): self
    {
        $options = [];

        foreach (($data['options'] ?? []) as $key => $definition) {
            if (is_array($definition)) {
                $options[(string) $key] = PostOption::fromArray((string) $key, $definition);
            }
        }

        return new self(
            accountId: (int) ($data['account_id'] ?? $data['accountId'] ?? 0),
            accountType: $data['account_type'] ?? $data['accountType'] ?? '',
            accountName: $data['account_name'] ?? $data['accountName'] ?? '',
            options: $options,
        );
    }

    public function toArray(): array
    {
        return [
            'account_id' => $this->accountId,
            'account_type' => $this->accountType,
            'account_name' => $this->accountName,
            'options' => array_map(fn (PostOption $o) => $o->toArray(), $this->options),
        ];
    }

    public function option(string $key): ?PostOption
    {
        return $this->options[$key] ?? null;
    }

    public function supports(string $key): bool
    {
        return isset($this->options[$key]);
    }

    /**
     * @return array<string>
     */
    public function keys(): array
    {
        return array_keys($this->options);
    }

    /**
     * @return array<string, PostOption>
     */
    public function requiredOptions(): array
    {
        return array_filter($this->options, fn (PostOption $o) => $o->required);
    }

    /**
     * Check an `options` payload against this account's definitions.
     *
     * Returns a map of option key to the reason it was rejected, so an empty
     * array means the payload is acceptable. Useful for failing fast before
     * spending a request on a post the platform will refuse.
     *
     * @param  array<string, mixed>  $options
     * @return array<string, string>
     */
    public function validate(array $options): array
    {
        $errors = [];

        foreach ($options as $key => $value) {
            $definition = $this->option((string) $key);

            if ($definition === null) {
                $errors[(string) $key] = "Option '{$key}' is not supported by {$this->accountType} account {$this->accountId}.";

                continue;
            }

            if (! $definition->accepts($value)) {
                $errors[(string) $key] = $definition->isDropdown()
                    ? "Option '{$key}' must be one of: ".implode(', ', $definition->choiceValues()).'.'
                    : "Option '{$key}' exceeds its maximum length of {$definition->maxLength}.";
            }
        }

        foreach ($this->requiredOptions() as $key => $definition) {
            if (! array_key_exists($key, $options)) {
                $errors[$key] = "Option '{$key}' is required by {$this->accountType} account {$this->accountId}.";
            }
        }

        return $errors;
    }
}
