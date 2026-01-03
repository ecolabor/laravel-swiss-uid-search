<?php

declare(strict_types=1);

namespace Ecolabor\SwissUid\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Ecolabor\SwissUid\Facades\SwissUid;

class ValidSwissUid implements ValidationRule
{
    protected bool $checkExists;

    public function __construct(bool $checkExists = false)
    {
        $this->checkExists = $checkExists;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail('Die :attribute muss ein String sein.');
            return;
        }

        $normalized = SwissUid::normalizeUid($value);

        // Check format: must be exactly 9 digits
        if (strlen($normalized) !== 9) {
            $fail('Die :attribute muss eine gültige Schweizer UID sein (9 Ziffern).');
            return;
        }

        // Validate checksum (Luhn algorithm variant)
        if (! $this->validateChecksum($normalized)) {
            $fail('Die :attribute hat eine ungültige Prüfziffer.');
            return;
        }

        // Optionally check if UID exists in the register
        if ($this->checkExists && ! SwissUid::validateUid($value)) {
            $fail('Die :attribute wurde im UID-Register nicht gefunden.');
        }
    }

    /**
     * Validate the UID checksum using a weighted sum algorithm.
     */
    protected function validateChecksum(string $uid): bool
    {
        if (strlen($uid) !== 9) {
            return false;
        }

        $weights = [5, 4, 3, 2, 7, 6, 5, 4];
        $sum = 0;

        for ($i = 0; $i < 8; $i++) {
            $sum += (int) $uid[$i] * $weights[$i];
        }

        $remainder = $sum % 11;
        $checkDigit = (11 - $remainder) % 11;

        // If checkDigit is 10, the UID is invalid
        if ($checkDigit === 10) {
            return false;
        }

        return $checkDigit === (int) $uid[8];
    }

    /**
     * Create a rule that only validates the format.
     */
    public static function format(): self
    {
        return new self(checkExists: false);
    }

    /**
     * Create a rule that validates the format and checks existence.
     */
    public static function exists(): self
    {
        return new self(checkExists: true);
    }
}
