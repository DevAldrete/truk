<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

/**
 * Validates the shape of a Mexican RFC (tax identification number).
 *
 * The check digit is not verified: a wrong digit only surfaces when a fiscal
 * document is issued, which happens behind the CFDI boundary.
 */
class Rfc implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (is_string($value) && preg_match('/^[A-ZÑ&]{3,4}\d{6}[A-Z0-9]{3}$/', $value) === 1) {
            return;
        }

        $fail('validation.rfc')->translate();
    }
}
