<?php

declare(strict_types=1);

namespace Kadevland\EasyModules\Traits;

use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputArgument;

/**
 * Resolves file paths and namespaces for modules in a modular architecture.
 *
 * This trait provides methods to resolve file paths and namespaces
 * for modules, designed for use in Laravel artisan commands that generate
 * module-specific files following Clean Architecture principles.
 *
 * @package Kadevland\EasyModules\Traits
 * @author  Kadevland <kadevland@kaosland.net>
 * @version 1.0.0
 */
trait ResolvesModulePathAndNamespaceCommand
{
    use PathNamespaceConverter;
    use ManagesSuffixes;

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // CORE MODULE RESOLUTION
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Get the module name from command arguments.
     *
     * Retrieves the module name from the command line arguments and converts
     * it to StudlyCase format for consistent naming across the system.
     *
     * @return string The module name in StudlyCase format
     */
    protected function getModuleInput(): string
    {
        return Str::studly(trim($this->argument('module')));
    }

    /**
     * Get the root namespace for the specified module.
     *
     * Constructs the complete root namespace of the module by combining
     * the configured base namespace with the specific module name.
     *
     * @return string The complete root namespace of the module
     *
     * @example
     * // With base_namespace: 'App\Modules' and module: 'Blog'
     * rootModuleNamespace() → 'App\Modules\Blog'
     */
    protected function rootModuleNamespace(): string
    {
        $module = $this->getModuleInput();
        return $this->laravel['config']->get('easymodules.base_namespace', 'App\\Modules')."\\{$module}";
    }

    /**
     * Get the root path for the specified module.
     *
     * Constructs the complete root filesystem path of the module by combining
     * the configured base path with the specific module name.
     *
     * @return string The complete root path of the module
     *
     * @example
     * // With base_path: 'app/Modules' and module: 'Blog'
     * rootModulePath() → 'app/Modules/Blog'
     */
    protected function rootModulePath(): string
    {
        $module = $this->getModuleInput();
        return $this->laravel['config']->get('easymodules.base_path', app_path('Modules')).DIRECTORY_SEPARATOR."{$module}";
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // COMPONENT RESOLUTION
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Get the namespace for a specific component type in the module.
     *
     * Resolves the complete namespace for a component by combining the module's
     * root namespace with the component-specific path. Uses configuration-based
     * paths with fallback to default Clean Architecture structure.
     *
     * @param string $type The component type (e.g., 'controller', 'model', 'service')
     * @param string $default The default path if configuration is not defined
     * @return string The complete namespace for the specified type
     *
     * @example
     * moduleNamespace('controller', 'Presentation\Http\Controllers')
     * → 'App\Modules\Blog\Presentation\Http\Controllers'
     */
    protected function moduleNamespace(string $type, string $default): string
    {
        $path = $this->laravel['config']->get("easymodules.paths.{$type}", $default);
        return $this->rootModuleNamespace()."\\".$this->pathToNamespace($path);
    }

    /**
     * Get the filesystem path for a specific component type in the module.
     *
     * Resolves the complete filesystem path for a component by combining the
     * module's root path with the component-specific directory. Uses configuration-based
     * paths with fallback to default Clean Architecture structure.
     *
     * @param string $type The component type (e.g., 'controller', 'model', 'service')
     * @param string $default The default path if configuration is not defined
     * @return string The complete filesystem path for the specified type
     *
     * @example
     * modulePath('controller', 'Presentation/Http/Controllers')
     * → 'app/Modules/Blog/Presentation/Http/Controllers'
     */
    protected function modulePath(string $type, string $default): string
    {
        $path = $this->laravel['config']->get("easymodules.paths.{$type}", $default);
        return $this->rootModulePath().DIRECTORY_SEPARATOR.$path;
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // SPECIALIZED PATH RESOLUTION
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Get the view directory path for the specified module.
     *
     * Resolves the path to the module's view directory, following Laravel's
     * view organization conventions within the modular structure.
     *
     * @param string $path Relative path to append to the base view path
     * @return string The complete path to the view directory
     *
     * @example
     * viewPath('emails') → 'app/Modules/Blog/Presentation/resources/views/emails'
     * viewPath() → 'app/Modules/Blog/Presentation/resources/views'
     */
    protected function viewPath($path = ''): string
    {
        $module = Str::lower($this->getModuleInput());
        $views  = $this->modulePath('view', 'Presentation/resources/views');
        return $views.($path ? DIRECTORY_SEPARATOR.$path : $path);
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // COMMAND INTEGRATION
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Get command arguments with the 'module' argument prepended.
     *
     * This method ensures that module-based commands always have the 'module'
     * argument available, even when extending from Laravel's base commands
     * that don't expect this argument.
     *
     * @return array<int, array<int, mixed>> The command arguments
     */
    protected function getArguments(): array
    {
        return $this->prepandModuleInput();
    }

    /**
     * Add the 'module' argument to the beginning of the arguments list.
     *
     * Prepends the required 'module' argument to the existing command arguments,
     * ensuring module-based commands have access to the module context while
     * maintaining compatibility with parent command structures.
     *
     * @return array<int, array<int, mixed>> The complete list of arguments including the 'module' argument
     */
    protected function prepandModuleInput(): array
    {
        $parentArguments = method_exists($this, 'getParentArguments')
            ? $this->getParentArguments()
            : (method_exists(get_parent_class($this), 'getArguments') ? parent::getArguments() : []);

        return array_merge([
            ['module', InputArgument::REQUIRED, 'The name of the module'],
        ], $parentArguments);
    }
}
