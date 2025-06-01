<?php

declare(strict_types=1);

namespace Kadevland\EasyModules\Commands\Make\Laravel;

use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Kadevland\EasyModules\Traits\HandlesModuleMakeCommands;
use Illuminate\Foundation\Console\MailMakeCommand as BaseMailMakeCommand;

/**
 * Command to create mail classes within modules
 *
 * This command extends Laravel's base MailMakeCommand to generate
 * mailable classes within the modular structure, supporting all Laravel
 * options like --markdown and --view with proper module-aware view resolution.
 *
 * @package Kadevland\EasyModules\Commands\Make\Laravel
 * @author  Kadevland <kadevland@kaosland.net>
 * @version 1.0.0
 */
class MailMakeCommand extends BaseMailMakeCommand
{
    use HandlesModuleMakeCommands;

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // PROPERTIES
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * The component type for this command
     *
     * @var string
     */
    protected string $componentType = 'mail';

    /**
     * The console command name
     *
     * @var string
     */
    protected $name = 'easymodules:make-mail';

    /**
     * The console command description
     *
     * @var string
     */
    protected $description = 'Create a new email class within a module';

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // CONFIGURATION
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Configure the command options and aliases
     *
     * @return void
     */
    protected function configure(): void
    {
        $this->configureModuleAliases('make-mail');
        parent::configure();
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // CORE GENERATOR METHODS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Get the destination class path within the module
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
     * Get the default namespace for the class within the module
     *
     * @param string $rootNamespace The root namespace of the application
     * @return string The default namespace for this component type in the module
     */
    protected function getDefaultNamespace($rootNamespace): string
    {
        return $this->moduleNamespace($this->getComponentType(), 'Infrastructure\\Mail');
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // VIEW PATH RESOLUTION METHODS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Get the view path relative to the module
     *
     * Override Laravel's viewPath to redirect to module views directory.
     *
     * @param string $path Optional path to append
     * @return string The view path within the module
     */
    protected function viewPath($path = ''): string
    {
        $moduleViewsPath = $this->modulePath('view', 'Presentation/resources/views');
        return $moduleViewsPath.($path ? DIRECTORY_SEPARATOR.$path : $path);
    }
}
