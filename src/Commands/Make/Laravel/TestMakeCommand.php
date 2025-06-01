<?php

declare(strict_types=1);

namespace Kadevland\EasyModules\Commands\Make\Laravel;

use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputOption;
use Kadevland\EasyModules\Traits\HandlesModuleMakeCommands;
use Illuminate\Foundation\Console\TestMakeCommand as BaseMakeTestMakeCommand;

/**
 * Command to create test classes within modules.
 *
 * This command extends Laravel's base TestMakeCommand to generate
 * test classes within the modular structure, supporting all Laravel
 * options like --unit, --pest, and custom path options with intelligent
 * path resolution for module-specific test organization.
 *
 * @package Kadevland\EasyModules\Commands\Make\Laravel
 * @author  Kadevland <kadevland@kaosland.net>
 * @version 1.0.0
 */
class TestMakeCommand extends BaseMakeTestMakeCommand
{
    use HandlesModuleMakeCommands;

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // PROPERTIES
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * The component type for this command.
     *
     * @var string
     */
    protected string $componentType = 'test';

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'easymodules:make-test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new test class within a module';

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // CONFIGURATION
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Configure the command options and aliases.
     *
     * @return void
     */
    protected function configure(): void
    {
        $this->configureModuleAliases('make-test');
        parent::configure();
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions(): array
    {
        return array_merge(parent::getOptions(), [
            ['path', 'p', InputOption::VALUE_OPTIONAL, 'Subfolder path after Tests/Unit or Tests/Feature'],
            ['subfolder', 's', InputOption::VALUE_OPTIONAL, 'Alias for --path'],
        ]);
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // CORE GENERATOR METHODS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Get the destination class path within the module.
     *
     * @param string $name The fully qualified class name
     * @return string The file path where the class should be created
     */
    protected function getPath($name): string
    {
        $name = Str::replaceFirst($this->rootNamespace(), '', $name);
        return $this->rootModulePath().'/'.ltrim(str_replace('\\', '/', $name).'.php', '/');
    }

    /**
     * Get the default namespace for the class within the module.
     *
     * This method determines the appropriate namespace based on test type (unit vs feature)
     * and applies any custom path options provided by the user.
     *
     * @param string $rootNamespace The root namespace of the application
     * @return string The default namespace for this component type in the module
     */
    protected function getDefaultNamespace($rootNamespace): string
    {
        $type          = $this->option('unit') ? 'unittest' : 'featuretest';
        $baseNamespace = $this->moduleNamespace($type, $this->getDefaultPath());

        // Apply custom path if provided
        if ($pathKey = $this->option('path') ?: $this->option('subfolder')) {
            $resolvedPath  = $this->resolveTestPath($pathKey);
            $subfolder     = str_replace('/', '\\', $resolvedPath);
            $baseNamespace .= '\\'.$subfolder;
        }

        return $baseNamespace;
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
    // PATH RESOLUTION METHODS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Get the default path for test generation based on test type.
     *
     * Returns the appropriate default directory path for test file generation,
     * following Laravel's standard testing conventions. This method serves as
     * a fallback when configuration paths are not explicitly defined.
     *
     * @return string The default namespace path for the test type
     */
    protected function getDefaultPath(): string
    {
        return $this->option('unit') ? 'Tests\\Unit' : 'Tests\\Feature';
    }

    /**
     * Resolve test path from configuration or use as-is.
     *
     * This method checks if the provided path key exists in the test_paths
     * configuration. If found, it uses the configured path; otherwise,
     * it treats the key as a literal path.
     *
     * @param string $pathKey The path key to resolve
     * @return string The resolved path
     */
    protected function resolveTestPath(string $pathKey): string
    {
        $testPaths = $this->laravel['config']->get('easymodules.test_paths', []);

        return $testPaths[$pathKey] ?? $pathKey;
    }
}
