<?php

namespace App\Support;

/**
 * The company's departments, offered in forms and filters across the
 * Visitors, Biometrics and Access Control modules. Single source of
 * truth so the three modules can never drift apart.
 */
class Departments
{
    public const ALL = [
        'Reception', 'Management', 'Human Resources', 'Finance', 'IT',
        'Operations', 'Security', 'Laboratory', 'Engineering', 'Legal',
    ];
}
