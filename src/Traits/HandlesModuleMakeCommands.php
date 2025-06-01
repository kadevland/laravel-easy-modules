<?php

declare(strict_types=1);

namespace Kadevland\EasyModules\Traits;

use Illuminate\Support\Collection;
use Symfony\Component\Finder\Finder;

/**
 * Handles common functionality for module make commands.
 *
 * This trait provides shared functionality for all make commands that generate
 * components within modules, including suffix management, stub resolution,
 * path handling, and component discovery for modular architecture.
 *
 * @package Kadevland\EasyModules\Traits
 * @author  Kadevland <kadevland@kaosland.net>
 * @version 1.0.0
 *
 * @method mixed argument(string $key) Gets command argument
 * @method mixed option(string $key) Gets command option
 * @method array|\Illuminate\Contracts\Foundation\Application laravel Laravel instance
 *
 * @property string $componentType The component type for this command
 */
trait HandlesModuleMakeCommands
{
    use CommandAliasManager;
    use ResolvesModulePathAndNamespaceCommand;

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // PROPERTIES & CONFIGURATION
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Whether this command generates a PHP class (requires StudlyCase) or a file.
     *
     * @var bool
     */
    protected bool $generatesClass = true;

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // CORE METHODS - COMPONENT TYPE & NAME HANDLING
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Get the component type for configuration lookup.
     *
     * @return string The component type (e.g., 'controller', 'model', 'service')
     */
    protected function getComponentType(): string
    {
        if (property_exists($this, 'componentType')) {
            return $this->componentType;
        }

        // Fallback: infer from class name
        $className = class_basename(static::class);
        $type      = str_replace(['MakeCommand', 'Command'], '', $className);

        return strtolower($type);
    }

    /**
     * Get the desired class name with proper casing and suffix handling.
     *
     * @return string The class name with proper casing and suffix applied if configured
     */
    protected function getNameInput(): string
    {
        $name = trim($this->argument('name'));

        if ($this->generatesClass) {
            // Handle nested namespaces: article/post → Article/Post
            $name = $this->toStudlyNamespace($name, '/');
        }

        // Apply suffix if configured
        if ($this->shouldAppendSuffix()) {
            $suffix = $this->getSuffixForType($this->getComponentType());
            $name   = $this->addSuffixIfMissing($name, $suffix);
        }

        return $name;
    }

    /**
     * Get the root namespace for the class.
     *
     * @return string The root namespace
     */
    protected function rootNamespace(): string
    {
        return $this->rootModuleNamespace();
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // CONFIGURATION MANAGEMENT
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Check if suffix should be appended based on configuration.
     *
     * @return bool
     */
    protected function shouldAppendSuffix(): bool
    {
        return $this->laravel['config']->get('easymodules.append_suffix', false);
    }

    /**
     * Get the suffix for the component type from configuration.
     *
     * @param string $type The component type
     * @return string The suffix for the type
     */
    protected function getSuffixForType(string $type): string
    {
        return $this->laravel['config']->get("easymodules.suffixes.{$type}", '');
    }

    /**
     * Configure command aliases for easy-modules.
     *
     * @param string $commandName The base command name (e.g., 'make-factory')
     * @return void
     */
    protected function configureModuleAliases(string $commandName): void
    {
        $this->configureEasyModulesAliases($commandName);

        if (str_starts_with($commandName, "make-")) {
            $this->configureEasyModulesAliases(str_replace('make-', '', $commandName));
        }
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // COMPONENT DISCOVERY METHODS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Get component class names from the current module with intelligent path resolution.
     *
     * This method automatically resolves the component path from configuration,
     * falling back to Clean Architecture defaults if not configured.
     *
     * @param string $componentType The component type (e.g., 'model', 'event', 'policy')
     * @param array $excludePatterns Patterns to exclude from results
     * @return array Array of component names
     */
    protected function getModuleComponents(
        string $componentType,
        array $excludePatterns = ['Abstract*', '*Trait', '*Interface']
    ): array {

        $componentPath = $this->modulePath($componentType, '');

        if (! is_dir($componentPath)) {
            return [];
        }

        return (new Collection(Finder::create()->files()
            ->depth(0)
            ->in($componentPath)))
            ->filter(fn ($file) => $file->getExtension() === 'php')
            ->map(fn ($file) => $file->getBasename('.php'))
            ->reject(function ($basename) use ($excludePatterns) {
                foreach ($excludePatterns as $pattern) {
                    if (fnmatch($pattern, $basename)) {
                        return true;
                    }
                }
                return false;
            })
            ->sort()
            ->values()
            ->all();
    }

    /**
     * Retrieves components with modular isolation control.
     *
     * Returns module components by default. Optionally includes global components
     * when callback provided (decision managed by calling command).
     *
     * @param string $type Component type (model|event|policy|observer|etc.)
     * @param callable(): array<string>|null $globalCallback Global component provider
     *
     * @return array<string> Deduplicated component class names
     *
     * @example Module-only: $this->getPossibleComponents('model')
     * @example With globals: $this->getPossibleComponents('model', fn() => parent::possibleModels())
     */
    protected function getPossibleComponents(string $type, ?callable $globalCallback = null): array
    {
        $moduleComponents = $this->getModuleComponents($type);

        if ($this->option('include-global') && $globalCallback) {
            $globalComponents = $globalCallback();
            return array_unique(array_merge($moduleComponents, $globalComponents));
        }

        return $moduleComponents;
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // STUB RESOLUTION SYSTEM
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Resolve the fully-qualified path to the stub file.
     *
     * @param string $stub The relative stub path
     * @return string The full path to the stub file
     */
    protected function resolveStubPath($stub): string
    {
        $easyModulesStub = $this->getConfiguredStubPath($stub);

        if ($easyModulesStub) {
            // Try custom stub in resources directory first
            $customPath = resource_path("stubs/{$easyModulesStub}");
            if (file_exists($customPath)) {
                return $customPath;
            }

            // Try package stubs directory
            $packagePath = $this->getPackageStubPath($easyModulesStub);
            if (file_exists($packagePath)) {
                return $packagePath;
            }
        }

        // Try Laravel's default behavior if parent method exists
        if (method_exists(get_parent_class($this), 'resolveStubPath')) {
            return parent::resolveStubPath($stub);
        }

        // Ultimate fallback
        return $this->getDefaultStubPath($stub);
    }

    /**
     * Get the configured stub path for the component type.
     *
     * @param string|null $stub The stub file path
     * @return string|null The configured stub path or null if not configured
     */
    protected function getConfiguredStubPath(?string $stub): ?string
    {
        if (! $stub) {
            return null;
        }

        $cleanStub = str_replace('/stubs/', '', $stub);
        $cleanStub = ltrim($cleanStub, '/');

        return $this->laravel['config']->get("easymodules.stubs.{$cleanStub}");
    }

    /**
     * Get the package stub file path.
     *
     * @param string $stubFile The stub file name
     * @return string The full path to the package stub
     */
    protected function getPackageStubPath(string $stubFile): string
    {
        return dirname(__DIR__, 2)."/src/stubs/{$stubFile}";
    }

    /**
     * Get the default Laravel stub path.
     *
     * @param string $stub The relative stub path
     * @return string The default stub path
     */
    protected function getDefaultStubPath(string $stub): string
    {
        // Try Laravel's base path first
        $basePath = $this->laravel->basePath("stubs/{$stub}");
        if (file_exists($basePath)) {
            return $basePath;
        }

        // Fallback to package directory
        return dirname(__DIR__, 2)."/stubs/{$stub}";
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // COMMAND INTEGRATION & UTILITIES
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Get console command arguments with module prepended.
     *
     * Automatically adds 'module' argument only if it doesn't already exist,
     * supporting both $signature and $name command definitions.
     *
     * @return array<int, array<int, mixed>> The command arguments
     */
    protected function getArguments(): array
    {

        $parentArguments = parent::getArguments();

        // 🔍 Check if 'module' argument already exists (from $signature or parent)
        foreach ($parentArguments as $argument) {
            if (isset($argument[0]) && $argument[0] === 'module') {
                // Module argument already exists, don't add it
                return $parentArguments;
            }
        }

        return $this->prepandModuleInput();
    }

    /**
     * Handle test creation when --test option is provided.
     *
     * @param string $path The component path
     * @return bool Whether test creation was successful
     */
    protected function handleTestCreation($path): bool
    {
        if (! $this->option('test') && ! $this->option('pest') && ! $this->option('phpunit')) {
            return false;
        }

        $className = class_basename($this->getNameInput());
        $testName  = $className.'Test';
        $autoPath  = $this->getComponentType();

        return $this->call('easymodules:make-test', [
            'module'    => $this->getModuleInput(),
            'name'      => $testName,
            '--path'    => $autoPath,
            '--unit'    => $this->option('unit'),
            '--pest'    => $this->option('pest'),
            '--phpunit' => $this->option('phpunit'),
        ]) == 0;
    }
}
