<?php

declare(strict_types=1);

namespace Kadevland\EasyModules\Traits;

use InvalidArgumentException;

/**
 * Provides model parsing capabilities for module-based commands.
 *
 * This trait handles the resolution of model names within the modular
 * architecture, ensuring proper namespace construction and fallback
 * to global models when module models don't exist.
 *
 * @package Kadevland\EasyModules\Traits
 * @author  Kadevland <kadevland@kaosland.net>
 * @version 1.0.0
 */
trait ParsesModuleModels
{
    // ═══════════════════════════════════════════════════════════════════════════════════════
    // MODEL PARSING METHODS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Parse and resolve model name within module context.
     *
     * Transforms a model name into a fully qualified class name, prioritizing
     * models within the current module before falling back to global models.
     * This method follows a specific resolution order:
     *
     * 1. **Current module models** - Check if model exists in current module
     * 2. **Global Laravel models** - Fallback to App\Models namespace
     * 3. **Absolute namespaces** - Respect developer's explicit namespace choice
     *
     * The method also handles suffix management and StudlyCase conversion
     * to ensure consistent naming conventions across the modular architecture.
     *
     * @param string $model The model name to parse (supports nested paths like 'User/Profile')
     * @return string The fully qualified model class name
     * @throws InvalidArgumentException If model name contains invalid characters
     *
     * @example
     * parseModel('User') → 'App\Modules\Blog\Infrastructure\Models\User'
     * parseModel('user/profile') → 'App\Modules\Blog\Infrastructure\Models\User\Profile'
     * parseModel('\App\Models\User') → 'App\Models\User' (absolute namespace preserved)
     */
    protected function parseModel($model): string
    {
        // Validate model name for security and consistency
        if (preg_match('([^A-Za-z0-9_/\\\\])', $model)) {
            throw new InvalidArgumentException('Model name contains invalid characters.');
        }

        // Handle absolute namespaces (starting with backslash)
        if (str_starts_with($model, '\\')) {
            $model = trim($model, '\\');

            // Check if it's already a model from THIS module
            $currentModuleNamespace = $this->moduleNamespace('model', 'Infrastructure\\Models');
            if (str_starts_with($model, $currentModuleNamespace)) {
                return $model; // Already the correct modular model
            }

            // For absolute namespaces, respect the developer's choice
            // Don't force into module - return as-is
            return $model;
        }

        // Convert to StudlyCase namespace format (handles nested paths)
        $model = $this->toStudlyNamespace($model, '/');

        // Apply suffix if configured in the system
        if ($this->shouldAppendSuffix()) {
            $model = $this->addSuffixIfMissing($model, $this->getSuffixForType('model'));
        }

        // Construct the module-specific model namespace
        $moduleModelNamespace = $this->moduleNamespace('model', 'Infrastructure\\Models');
        $moduleModelClass     = $moduleModelNamespace.'\\'.$model;

        // Prefer module model if it exists, otherwise fall back to global resolution
        if (class_exists($moduleModelClass)) {
            return $moduleModelClass;
        }

        // Delegate to Laravel's standard model resolution as fallback
        return $this->qualifyModel($moduleModelClass);
    }
}
