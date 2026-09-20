<?php

namespace App\Rules;

use App\Support\Url;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class SafeDestinationUrl implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! Url::isSafe($value)) {
            $fail(__('qr.invalid_destination'));
        }
    }
}
