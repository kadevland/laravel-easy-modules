<?php

declare(strict_types=1);

namespace Kadevland\EasyModules\Traits;

/**
 * Manages command aliases in Laravel commands.
 *
 * This trait provides functionality to manage command aliases, allowing for easy
 * configuration of multiple aliases for a command and reducing code duplication.
 * It supports both generic alias management and EasyModules-specific patterns.
 *
 * @package Kadevland\EasyModules\Traits
 * @author  Kadevland <kadevland@kaosland.net>
 * @version 1.0.0
 */
trait CommandAliasManager
{
    // ═══════════════════════════════════════════════════════════════════════════════════════
    // GENERIC ALIAS MANAGEMENT
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Configure the command with provided aliases.
     *
     * This method sets up command aliases and ensures proper parent configuration
     * is called. It provides a clean interface for alias configuration while
     * maintaining compatibility with Laravel's command system.
     *
     * @param array<int, string> $aliases An array of command aliases
     * @return void
     */
    protected function configureWithAliases(array $aliases = []): void
    {
        if (! empty($aliases)) {
            $this->setAliases($aliases);
        }

        if (method_exists(get_parent_class($this), 'configure')) {
            parent::configure();
        }
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // EASYMODULES ALIAS PATTERNS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Configure command with predefined aliases for EasyModules commands.
     *
     * This method automatically generates a comprehensive set of aliases following
     * EasyModules conventions. It creates both prefixed variations and alternative
     * command names to provide maximum flexibility for developers.
     *
     * Generated patterns:
     * - `emodules:command-name`
     * - `emodule:command-name`
     * - `emodules:alternative-name` (if applicable)
     * - `emodule:alternative-name` (if applicable)
     *
     * @param string $baseCommandName The base name of the command (e.g., 'make-module', 'publish')
     * @param array<int, string> $additionalAliases Additional custom aliases to include
     * @return void
     *
     * @example
     * configureEasyModulesAliases('make-controller')
     * // Generates: emodules:make-controller, emodule:make-controller, emodules:controller, emodule:controller
     */
    protected function configureEasyModulesAliases(string $baseCommandName, array $additionalAliases = []): void
    {
        $prefixes         = ['emodules:', 'emodule:'];
        $alternativeNames = $this->getAlternativeCommandNames($baseCommandName);

        $aliases = [];

        // Generate standard prefix variations
        foreach ($prefixes as $prefix) {
            $aliases[] = $prefix.$baseCommandName;

            // Add aliases with alternative names if they exist
            foreach ($alternativeNames as $altName) {
                $aliases[] = $prefix.$altName;
            }
        }

        // Include any additional custom aliases
        if (! empty($additionalAliases)) {
            $aliases = array_merge($aliases, $additionalAliases);
        }

        $this->configureWithAliases($aliases);
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // ALIAS GENERATION HELPERS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Get alternative command names for common EasyModules commands.
     *
     * This method provides a mapping of base command names to their alternative
     * forms, enabling more intuitive command usage. For example, 'make-controller'
     * can also be called as just 'controller'.
     *
     * @param string $baseCommandName The base command name
     * @return array<int, string> Array of alternative command names
     *
     * @example
     * getAlternativeCommandNames('make-controller') → ['controller']
     * getAlternativeCommandNames('list') → ['modules']
     */
    protected function getAlternativeCommandNames(string $baseCommandName): array
    {
        $alternativeNames = [
            'list' => ['modules'],
        ];

        // For make commands, provide the component name as an alternative
        if (str_starts_with($baseCommandName, 'make-')) {
            $alternativeNames[$baseCommandName] = [substr($baseCommandName, strlen('make-'))];
        }

        return $alternativeNames[$baseCommandName] ?? [];
    }
}
