<?php

declare(strict_types=1);

namespace Kadevland\EasyModules\Generators;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;
use Kadevland\EasyModules\Traits\PathNamespaceConverter;

/**
 * Generator for creating scaffold files within a module.
 *
 * This class creates essential scaffold files for a module including
 * the service provider, configuration files, and route definitions.
 * It uses stub templates and variable replacement to create customized files.
 *
 * @package Kadevland\EasyModules\Generators
 * @author  Kadevland <kadevland@kaosland.net>
 * @version 1.0.0
 */
class ScaffoldModuleFileGenerator
{
    use PathNamespaceConverter;

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // PROPERTIES
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Module configuration settings.
     *
     * @var array
     */
    protected array $settings;

    /**
     * Module name.
     *
     * @var string
     */
    protected string $name;

    /**
     * Base path for the module.
     *
     * @var string
     */
    protected string $basePath = '';

    /**
     * Base namespace for the module.
     *
     * @var string
     */
    protected string $baseNamespace = '';

    /**
     * Successfully generated files.
     *
     * @var array
     */
    protected array $generatedFiles = [];

    /**
     * Failed file generations.
     *
     * @var array
     */
    protected array $failedFiles = [];

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // CONSTRUCTOR & INITIALIZATION
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Create a new ScaffoldModuleFileGenerator instance.
     *
     * @param array $settings Module configuration settings
     * @param string $name Module name
     */
    public function __construct(array $settings, string $name)
    {
        $this->settings = $settings;
        $this->name     = $name;

        $this->initializeBasePath();
        $this->initializeBaseNamespace();
    }

    /**
     * Initialize the base path for the module.
     *
     * @return void
     */
    protected function initializeBasePath(): void
    {
        $basePath       = Arr::get($this->settings, 'base_path', app_path('Modules'));
        $this->basePath = $basePath.'/'.$this->name;
    }

    /**
     * Initialize the base namespace for the module.
     *
     * @return void
     */
    protected function initializeBaseNamespace(): void
    {
        $baseNamespace       = Arr::get($this->settings, 'base_namespace', 'App\\Modules');
        $this->baseNamespace = $baseNamespace.'\\'.$this->name;
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // MAIN GENERATION METHODS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Generate all scaffold files for the module.
     *
     * @return bool Whether generation was successful
     */
    public function generate(): bool
    {
        $scaffoldStubs = $this->getScaffoldStubs();
        $replaces      = $this->buildReplacementVariables();

        $overallSuccess = true;

        foreach ($scaffoldStubs as $type => $stubFile) {
            $success = $this->generateScaffoldFile($type, $stubFile, $replaces);

            if ($success) {
                $this->logGeneratedFile($type);
            } else {
                $this->logFailedFile($type, $stubFile);
                $overallSuccess = false;
            }
        }

        // Auto-register the module provider if auto-discovery is enabled
        if ($overallSuccess && $this->shouldAutoDiscover()) {
            $this->publishServiceProvider();
        }

        return $overallSuccess;
    }

    /**
     * Generate a single scaffold file.
     *
     * @param string $type The scaffold type
     * @param string $stubFile The stub file to use
     * @param array $replaces Replacement variables
     * @return bool Whether generation was successful
     */
    protected function generateScaffoldFile(string $type, string $stubFile, array $replaces): bool
    {
        $targetPath = $this->getTargetFilePath($type);

        if (! $targetPath) {
            return false;
        }

        return $this->createFileFromStub($targetPath, $stubFile, $replaces);
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // PATH RESOLUTION METHODS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Get the target file path for a specific scaffold type.
     *
     * @param string $type The scaffold type
     * @return string|null The target file path or null if not supported
     */
    protected function getTargetFilePath(string $type): ?string
    {
        return match ($type) {
            'config'           => $this->getConfigFilePath(),
            'service_provider' => $this->getServiceProviderFilePath(),
            'route_web'        => $this->getRouteFilePath('web'),
            'route_api'        => $this->getRouteFilePath('api'),
            'route_console'    => $this->getRouteFilePath('console'),
            default            => $this->getCustomScaffoldFilePath($type),
        };
    }

    /**
     * Get the file path for the module configuration file.
     *
     * @return string|null The config file path
     */
    protected function getConfigFilePath(): ?string
    {
        $configPath = $this->getPathFromSettings('config');

        if (! $configPath) {
            return null;
        }

        return $this->generatePhpFilePath($this->basePath.'/'.$configPath.'/config');
    }

    /**
     * Get the file path for the service provider.
     *
     * @return string|null The service provider file path
     */
    protected function getServiceProviderFilePath(): ?string
    {
        $providerPath = $this->getPathFromSettings('provider');

        if (! $providerPath) {
            return null;
        }

        $className = $this->name.'ServiceProvider';
        return $this->generatePhpFilePath($this->basePath.'/'.$providerPath.'/'.$className);
    }

    /**
     * Get the file path for a specific route file.
     *
     * @param string $routeType The route type (web, api, console)
     * @return string|null The route file path
     */
    protected function getRouteFilePath(string $routeType): ?string
    {
        $routePath = $this->getPathFromSettings('routes');

        if (! $routePath) {
            return null;
        }

        return $this->generatePhpFilePath($this->basePath.'/'.$routePath.'/'.$routeType);
    }

    /**
     * Get the file path for a custom scaffold type.
     *
     * @param string $type The scaffold type
     * @return string|null The custom scaffold file path
     */
    protected function getCustomScaffoldFilePath(string $type): ?string
    {
        $path = $this->getPathFromSettings($type);

        if (! $path) {
            return null;
        }

        return $this->generatePhpFilePath($this->basePath.'/'.$path.'/'.$type);
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // STUB REPLACEMENT SYSTEM
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Build the replacement variables for stub processing.
     *
     * @return array<string, string> The replacement variables
     */
    protected function buildReplacementVariables(): array
    {
        $replaces = [];

        $this->addReplacement('name', $this->name, $replaces);
        $this->addReplacement('class', $this->name.'ServiceProvider', $replaces);

        $providerPath      = $this->getPathFromSettings('provider') ?? 'Providers';
        $providerNamespace = $this->pathToNamespace($providerPath);
        $this->addReplacement('class_namespace', $this->baseNamespace.'\\'.$providerNamespace, $replaces);

        $this->addReplacement('base_namespace', $this->baseNamespace, $replaces);
        $this->addReplacement('scope_namespace', Str::lower($this->name), $replaces);
        $this->addReplacement('base_path', $this->getRelativeBasePath(), $replaces);

        $this->addPathReplacements($replaces);

        return $replaces;
    }

    /**
     * Add replacement variables for a key in various formats.
     *
     * @param string $key The replacement key
     * @param string $value The replacement value
     * @param array $replaces The replacements array (passed by reference)
     * @return void
     */
    protected function addReplacement(string $key, string $value, array &$replaces): void
    {
        $formats = [
            $key,
            Str::upper($key),
            Str::lower($key),
            Str::studly($key),
            Str::camel($key),
            Str::snake($key),
        ];

        foreach ($formats as $format) {
            $replaces["{{ {$format} }}"] = $value;
            $replaces["{{{$format}}}"]   = $value;
        }
    }

    /**
     * Add path-specific replacement variables.
     *
     * @param array $replaces The replacements array (passed by reference)
     * @return void
     */
    protected function addPathReplacements(array &$replaces): void
    {
        $paths = Arr::get($this->settings, 'paths', []);

        foreach ($paths as $key => $path) {
            $variableName = $key.'_path';
            $this->addReplacement($variableName, $path, $replaces);
        }
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // FILE OPERATIONS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Create a file from a stub template with replacements.
     *
     * @param string $targetPath The target file path
     * @param string $stubFile The stub file to use
     * @param array $replaces The replacement variables
     * @param bool $overwrite Whether to overwrite existing files
     * @return bool Whether the file was created successfully
     */
    protected function createFileFromStub(
        string $targetPath,
        string $stubFile,
        array $replaces = [],
        bool $overwrite = false
    ): bool {
        // If file exists and we don't want to overwrite, skip creation
        if (! $overwrite && File::exists($targetPath)) {
            return true;
        }

        // Ensure the directory exists
        $this->ensureDirectoryExists(dirname($targetPath));

        // Check if stub file exists
        $stubPath = $this->resolveStubPath($stubFile);
        if (! File::exists($stubPath)) {
            return false;
        }

        // Process the stub and create the file
        $content = $this->processStubFile($stubFile, $replaces);

        return File::put($targetPath, $content) !== false;
    }

    /**
     * Process a stub file and apply replacements.
     *
     * @param string $stubFile The stub file to process
     * @param array $replaces The replacement variables
     * @return string The processed content
     */
    protected function processStubFile(string $stubFile, array $replaces = []): string
    {
        $stubPath = $this->resolveStubPath($stubFile);
        $content  = File::get($stubPath);

        foreach ($replaces as $search => $replace) {
            $content = str_replace($search, $replace, $content);
        }

        return $content;
    }

    /**
     * Resolve the full path to a stub file.
     *
     * @param string $stubFile The stub file name
     * @return string The full path to the stub file
     */
    protected function resolveStubPath(string $stubFile): string
    {
        $stubFile = trim($stubFile, '/');

        // Check for custom stub in resources directory
        $customPath = resource_path("stubs/{$stubFile}");
        if (file_exists($customPath)) {
            return $customPath;
        }

        // Fallback to package stubs
        return dirname(__DIR__)."/stubs/{$stubFile}";
    }

    /**
     * Ensure a directory exists, creating it if necessary.
     *
     * @param string $path The directory path
     * @return void
     */
    protected function ensureDirectoryExists(string $path): void
    {
        if (! File::isDirectory($path)) {
            File::makeDirectory($path, 0755, true, true);
        }
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // AUTO-DISCOVERY & SERVICE PROVIDER REGISTRATION
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Check if auto-discovery is enabled and ServiceProvider should be registered.
     *
     * @return bool
     */
    protected function shouldAutoDiscover(): bool
    {
        $autoDiscoverEnabled = Arr::get($this->settings, 'auto_discover', true);

        if (! $autoDiscoverEnabled) {
            return false;
        }

        $serviceProviderPath = $this->getServiceProviderFilePath();

        return $serviceProviderPath && File::exists($serviceProviderPath);
    }

    /**
     * Register the module ServiceProvider for automatic loading.
     *
     * @return void
     */
    protected function publishServiceProvider(): void
    {
        try {
            $providerClass = $this->getServiceProviderClass();
            $bootstrapPath = app()->getBootstrapProvidersPath();

            ServiceProvider::addProviderToBootstrapFile(
                $providerClass,
                $bootstrapPath
            );

            $this->logGeneratedFile('provider_registration');
        } catch (\Exception $e) {
            $this->logFailedFile('provider_registration', $e->getMessage());
        }
    }

    /**
     * Get the fully qualified ServiceProvider class name.
     *
     * @return string The ServiceProvider class name
     */
    protected function getServiceProviderClass(): string
    {
        $providerPath      = $this->getPathFromSettings('provider') ?? 'Providers';
        $providerNamespace = $this->pathToNamespace($providerPath);

        return $this->baseNamespace.'\\'.$providerNamespace.'\\'.$this->name.'ServiceProvider';
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // LOGGING & STATE MANAGEMENT
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Log a successfully generated file.
     *
     * @param string $type The file type
     * @param string|null $path The file path
     * @return void
     */
    protected function logGeneratedFile(string $type, ?string $path = null): void
    {
        $this->generatedFiles[] = [
            'type'      => $type,
            'path'      => $path,
            'timestamp' => now(),
        ];
    }

    /**
     * Log a failed file generation.
     *
     * @param string $type The file type
     * @param string $reason The failure reason
     * @return void
     */
    protected function logFailedFile(string $type, string $reason): void
    {
        $this->failedFiles[] = [
            'type'      => $type,
            'reason'    => $reason,
            'timestamp' => now(),
        ];
    }

    /**
     * Get generated files for debugging.
     *
     * @return array<int, array<string, mixed>> The generated files
     */
    public function getGeneratedFiles(): array
    {
        return $this->generatedFiles;
    }

    /**
     * Get failed files for debugging.
     *
     * @return array<int, array<string, mixed>> The failed files
     */
    public function getFailedFiles(): array
    {
        return $this->failedFiles;
    }

    /**
     * Check if generation was successful.
     *
     * @return bool
     */
    public function wasSuccessful(): bool
    {
        return empty($this->failedFiles);
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // CONFIGURATION HELPERS & UTILITIES
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Get a path from settings using the paths configuration.
     *
     * @param string $key The configuration key
     * @return string|null The path or null if not found
     */
    protected function getPathFromSettings(string $key): ?string
    {
        return Arr::get($this->settings, "paths.{$key}");
    }

    /**
     * Get the scaffold stubs configuration.
     *
     * @return array<string, string> The scaffold stubs
     */
    protected function getScaffoldStubs(): array
    {
        return Arr::get($this->settings, 'stubs_scaffold', []);
    }

    /**
     * Get the relative base path (removing app_path prefix).
     *
     * @return string The relative base path
     */
    protected function getRelativeBasePath(): string
    {
        $fullBasePath = Arr::get($this->settings, 'base_path', app_path('Modules'));
        return str_replace(app_path('').'/', '', $this->basePath);
    }
}
