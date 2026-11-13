<?php

declare(strict_types=1);

namespace Concrete\Package\InnoShared\Infrastructure\Validation;

use Concrete\Package\InnoShared\Infrastructure\Exception\ValidationException;

class InputValidator
{
    /** @param array<string, mixed> $data */
    public function __construct(private readonly array $data) {}

    public function requireString(string $field, int $minLength, int $maxLength): string
    {
        $value = trim((string) ($this->data[$field] ?? ''));
        if ($value === '') {
            throw new ValidationException("{$field} is required.", ['field' => $field]);
        }
        if (mb_strlen($value) < $minLength) {
            throw new ValidationException("{$field} must be at least {$minLength} characters.", ['field' => $field]);
        }
        if (mb_strlen($value) > $maxLength) {
            throw new ValidationException("{$field} must not exceed {$maxLength} characters.", ['field' => $field]);
        }

        return $value;
    }

    public function optionalString(string $field, int $maxLength): ?string
    {
        if (!isset($this->data[$field]) || trim((string) $this->data[$field]) === '') {
            return null;
        }
        $value = trim((string) $this->data[$field]);
        if (mb_strlen($value) > $maxLength) {
            throw new ValidationException("{$field} must not exceed {$maxLength} characters.", ['field' => $field]);
        }

        return $value;
    }

    /** @param list<string> $allowed */
    public function requireEnum(string $field, array $allowed): string
    {
        $value = trim((string) ($this->data[$field] ?? ''));
        if (!in_array($value, $allowed, true)) {
            throw new ValidationException("{$field} must be one of: " . implode(', ', $allowed) . '.', ['field' => $field]);
        }

        return $value;
    }
}
