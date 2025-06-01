<?php

declare(strict_types=1);

namespace Kadevland\EasyModules\Traits;

use Illuminate\Support\Str;

/**
 * Comprehensive path and namespace manipulation utilities.
 *
 * This trait provides all path-to-namespace conversion utilities,
 * file path generation, StudlyCase transformation, custom separators,
 * and PHP file extension handling for the EasyModules system.
 *
 * @package Kadevland\EasyModules\Traits
 * @author  Kadevland <kadevland@kaosland.net>
 * @version 1.0.0
 */
trait PathNamespaceConverter
{
    // ═══════════════════════════════════════════════════════════════════════════════════════
    // BASIC PATH/NAMESPACE CONVERSION
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Convert a file path to namespace format.
     *
     * Transforms filesystem paths to PHP namespace format using backslashes.
     * Handles mixed separators and normalizes to standard namespace format.
     *
     * @param string $path The path to convert
     * @return string The path converted to namespace format
     *
     * @example
     * pathToNamespace('Domain/Entities') → 'Domain\Entities'
     * pathToNamespace('Infrastructure\\Models') → 'Infrastructure\Models'
     */
    protected function pathToNamespace(string $path): string
    {
        $path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
        return str_replace(DIRECTORY_SEPARATOR, '\\', $path);
    }

    /**
     * Convert a path to StudlyCase namespace format.
     *
     * Transforms filesystem paths to StudlyCase namespace format, converting
     * each segment to StudlyCase and joining with the specified separator.
     * Underscores (_) and hyphens (-) are treated as segment separators,
     * creating separate namespace segments.
     *
     * @param string $path The path to convert
     * @param string $separator The separator to use for the namespace (default: '\\')
     * @return string The path converted to StudlyCase format with the specified separator
     *
     * @example
     * toStudlyNamespace('application_services/user_service') → 'Application\Services\User\Service'
     * toStudlyNamespace('domain/entities', '/') → 'Domain/Entities'
     * toStudlyNamespace('snake_case_path') → 'Snake\Case\Path'
     * toStudlyNamespace('kebab-case-name') → 'Kebab\Case\Name'
     */
    protected function toStudlyNamespace(string $path, string $separator = '\\'): string
    {
        // Replace underscores and hyphens with slashes to create segments
        $path = str_replace(['_', '-'], '/', $path);
        // Normalize separators
        $path     = str_replace(['\\', '/'], '/', $path);
        $segments = explode('/', $path);
        $segments = array_filter($segments); // Remove empty segments
        $segments = array_map([Str::class, 'studly'], $segments);
        return implode($separator, $segments);
    }

    /**
     * Convert a path to namespace with optional StudlyCase transformation.
     *
     * Combines the functionality of pathToNamespace and toStudlyNamespace,
     * providing a one-stop method for path-to-namespace conversion.
     *
     * @param string $path The path to convert
     * @param bool $studlyCase Whether to apply StudlyCase transformation
     * @param string $separator The separator to use (only when $studlyCase is true)
     * @return string The converted namespace
     *
     * @example
     * convertPathToNamespace('Domain/Entities') → 'Domain\Entities'
     * convertPathToNamespace('application_services', true) → 'ApplicationServices'
     * convertPathToNamespace('domain/entities', true, '/') → 'Domain/Entities'
     */
    protected function convertPathToNamespace(
        string $path,
        bool $studlyCase = false,
        string $separator = '\\'
    ): string {
        return $studlyCase
            ? $this->toStudlyNamespace($path, $separator)
            : $this->pathToNamespace($path);
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // PATH NORMALIZATION & MANIPULATION
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Normalize a path by removing extra separators and empty segments.
     *
     * Cleans up filesystem paths by removing duplicate separators,
     * leading/trailing separators, and empty segments. Also trims
     * whitespace from the beginning and end of the path.
     *
     * @param string $path The path to normalize
     * @return string The normalized path
     *
     * @example
     * normalizePath('//Domain//Entities/') → 'Domain/Entities'
     * normalizePath('\\Application\\\\Services\\') → 'Application/Services'
     * normalizePath('  /Domain//Entities/  ') → 'Domain/Entities'
     */
    protected function normalizePath(string $path): string
    {
        // Trim whitespace first
        $path = trim($path);

        // Convert all separators to forward slashes
        $path = str_replace(['\\', '/'], '/', $path);

        // Remove multiple consecutive slashes
        $path = preg_replace('/\/+/', '/', $path);

        // Remove leading and trailing slashes
        $path = trim($path, '/');

        return $path;
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // NAMESPACE SEGMENTATION
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Split a namespace into its component segments.
     *
     * Breaks down a namespace string into individual segments,
     * handling both forward and backward slashes.
     *
     * @param string $namespace The namespace to split
     * @return array<string> The namespace segments
     *
     * @example
     * splitNamespace('App\Modules\Blog\Domain\Entities') → ['App', 'Modules', 'Blog', 'Domain', 'Entities']
     * splitNamespace('Domain/Services/UserService') → ['Domain', 'Services', 'UserService']
     */
    protected function splitNamespace(string $namespace): array
    {
        $normalized = $this->normalizePath($namespace);
        return $normalized ? explode('/', $normalized) : [];
    }

    /**
     * Join namespace segments with the specified separator.
     *
     * Combines namespace segments into a complete namespace string
     * using the provided separator.
     *
     * @param array<string> $segments The namespace segments
     * @param string $separator The separator to use (default: '\\')
     * @return string The joined namespace
     *
     * @example
     * joinNamespace(['Domain', 'Entities', 'User']) → 'Domain\Entities\User'
     * joinNamespace(['App', 'Modules', 'Blog'], '/') → 'App/Modules/Blog'
     */
    protected function joinNamespace(array $segments, string $separator = '\\'): string
    {
        return implode($separator, array_filter($segments));
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // PHP FILE PATH GENERATION
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Generate a PHP file path by ensuring it has the correct extension.
     *
     * Automatically adds '.php' extension if no extension is present or
     * replaces '.stub' extension with '.php' if it exists.
     * Handles directory separators correctly across platforms.
     *
     * @param string $path The original file path
     * @return string The file path with the proper PHP extension
     *
     * @example
     * generatePhpFilePath('UserController') → 'UserController.php'
     * generatePhpFilePath('UserController.stub') → 'UserController.php'
     * generatePhpFilePath('UserController.php') → 'UserController.php'
     */
    protected function generatePhpFilePath(string $path): string
    {
        $pathInfo = pathinfo($path);

        // If there's no extension, add .php
        if (! isset($pathInfo['extension'])) {
            return $path.'.php';
        }

        // If the extension is 'stub', replace it with 'php'
        if (strtolower($pathInfo['extension']) === 'stub') {
            $dirname = $pathInfo['dirname'];
            // Handle the case where dirname is '.'
            if ($dirname === '.') {
                return $pathInfo['filename'].'.php';
            }
            return $dirname.DIRECTORY_SEPARATOR.$pathInfo['filename'].'.php';
        }

        // Otherwise, return the original path
        return $path;
    }

    /**
     * Ensure a file path has a specific extension.
     *
     * More flexible version that can handle any extension,
     * not just PHP files.
     *
     * @param string $path The original file path
     * @param string $extension The desired extension (with or without dot)
     * @param bool $replaceExisting Whether to replace existing extensions
     * @return string The file path with the specified extension
     *
     * @example
     * ensureFileExtension('config', 'php') → 'config.php'
     * ensureFileExtension('test.stub', '.php', true) → 'test.php'
     * ensureFileExtension('file.txt', 'php', false) → 'file.txt'
     */
    protected function ensureFileExtension(string $path, string $extension, bool $replaceExisting = true): string
    {
        $extension = ltrim($extension, '.');
        $pathInfo  = pathinfo($path);

        // No extension present - add it
        if (! isset($pathInfo['extension'])) {
            return $path.'.'.$extension;
        }

        // Extension exists and we should replace it
        if ($replaceExisting || strtolower($pathInfo['extension']) === 'stub') {
            $dirname = $pathInfo['dirname'];
            // Handle the case where dirname is '.'
            if ($dirname === '.') {
                return $pathInfo['filename'].'.'.$extension;
            }
            return $dirname.DIRECTORY_SEPARATOR.$pathInfo['filename'].'.'.$extension;
        }

        // Keep existing extension
        return $path;
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // COMPLETE FILE PATH CONSTRUCTION
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Build a complete file path from namespace and filename.
     *
     * Converts a namespace to a file path structure and appends
     * the filename with proper extension handling.
     *
     * @param string $namespace The namespace (e.g., 'App\Modules\Blog\Domain\Entities')
     * @param string $filename The filename (e.g., 'User')
     * @param string $extension The file extension (default: 'php')
     * @param string $basePath The base path to prepend
     * @return string The complete file path
     *
     * @example
     * buildFilePathFromNamespace('App\Domain\Entities', 'User', 'php', '/var/www')
     * → '/var/www/App/Domain/Entities/User.php'
     */
    protected function buildFilePathFromNamespace(
        string $namespace,
        string $filename,
        string $extension = 'php',
        string $basePath = ''
    ): string {
        $path = str_replace('\\', DIRECTORY_SEPARATOR, $namespace);

        // If the filename already has an extension, don't add another one
        $pathInfo = pathinfo($filename);
        if (isset($pathInfo['extension'])) {
            $fullFilename = $filename; // Keep as is
        } else {
            $fullFilename = $filename.'.'.$extension;
        }

        $parts = array_filter([$basePath, $path, $fullFilename]);
        return implode(DIRECTORY_SEPARATOR, $parts);
    }
}
