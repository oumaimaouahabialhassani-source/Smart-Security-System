<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Validation\Rules\Password;

class PasswordPolicy
{
    /**
     * Build the password validation rule from the Security settings,
     * so administrators control the policy without touching code.
     */
    public static function rule(): Password
    {
        $rule = Password::min((int) Setting::get('security.password_min_length', 8));

        if (Setting::get('security.password_require_uppercase')) {
            $rule->mixedCase();
        }

        if (Setting::get('security.password_require_numbers')) {
            $rule->numbers();
        }

        if (Setting::get('security.password_require_symbols')) {
            $rule->symbols();
        }

        return $rule;
    }
}
