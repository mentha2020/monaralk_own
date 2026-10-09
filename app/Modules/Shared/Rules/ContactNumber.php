<?php

namespace App\Modules\Shared\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ContactNumber implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        $digits = preg_replace('/\D+/', '', (string) $value);

        if ($digits === null || strlen($digits) < 8 || strlen($digits) > 15) {
            $fail('The :attribute must contain between 8 and 15 digits.');
        }
    }
}
