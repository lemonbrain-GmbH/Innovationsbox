<?php

declare(strict_types=1);

namespace Concrete\Package\InnoShared\Infrastructure\Adapter\Logger;

class SensitiveDataProcessor
{
    private const SENSITIVE_KEYS = [
        'password',
        'token',
        'secret',
        'authorization',
        'cookie',
        'credit_card',
    ];

    private const REDACTED = '***REDACTED***';

    /**
     * @param array<string, mixed> $record
     * @return array<string, mixed>
     */
    public function __invoke(array $record): array
    {
        if (isset($record['context']) && is_array($record['context'])) {
            $record['context'] = $this->scrub($record['context']);
        }

        return $record;
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function scrub(array $data): array
    {
        foreach ($data as $key => $value) {
            /** @var string|int $key */
            if (is_string($key) && $this->isSensitive($key)) {
                $data[$key] = self::REDACTED;
            } elseif (is_array($value)) {
                $data[$key] = $this->scrub($value);
            }
        }

        return $data;
    }

    private function isSensitive(string $key): bool
    {
        $lower = strtolower($key);
        foreach (self::SENSITIVE_KEYS as $pattern) {
            if (str_contains($lower, $pattern)) {
                return true;
            }
        }

        return false;
    }
}
