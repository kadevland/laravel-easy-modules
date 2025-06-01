<?php

declare(strict_types=1);

namespace Kadevland\EasyModules\Traits;

use Illuminate\Support\Str;

/**
 * Manages suffixes in strings.
 *
 * This trait provides utility methods for handling string suffixes,
 * particularly useful in code generation scenarios where consistent
 * naming conventions with suffixes are required.
 *
 * @package Kadevland\EasyModules\Traits
 * @author  Kadevland <kadevland@kaosland.net>
 * @version 1.0.0
 */
trait ManagesSuffixes
{
    // ═══════════════════════════════════════════════════════════════════════════════════════
    // SUFFIX MANIPULATION METHODS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Add a suffix to the name if it doesn't already have it.
     *
     * This method intelligently checks if a string already ends with the specified
     * suffix (case-insensitive) and adds it only if missing. This prevents duplicate
     * suffixes and ensures consistent naming conventions.
     *
     * @param string $name The name to add the suffix to
     * @param string $suffix The suffix to add
     * @return string The name with the suffix added if necessary
     *
     * @example
     * addSuffixIfMissing('User', 'Controller') → 'UserController'
     * addSuffixIfMissing('UserController', 'Controller') → 'UserController' (unchanged)
     * addSuffixIfMissing('user', 'CONTROLLER') → 'userCONTROLLER' (case preserved)
     */
    protected function addSuffixIfMissing(string $name, string $suffix): string
    {
        return Str::endsWith(Str::lower($name), Str::lower($suffix)) ? $name : $name . $suffix;
    }
}
