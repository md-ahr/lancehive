<?php

declare(strict_types=1);

namespace App\Features\Settings\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final class ValidIanaTimezone implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! in_array($value, timezone_identifiers_list(), true)) {
            $fail('The :attribute must be a valid IANA timezone identifier.');
        }
    }
}
