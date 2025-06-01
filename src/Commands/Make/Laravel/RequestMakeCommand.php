<?php

declare(strict_types=1);

namespace Kadevland\EasyModules\Commands\Make\Laravel;

use Illuminate\Support\Str;
use Kadevland\EasyModules\Traits\HandlesModuleMakeCommands;
use Illuminate\Foundation\Console\RequestMakeCommand as BaseRequestMakeCommand;

/**
 * Command to create form request classes within modules.
 *
 * This command extends Laravel's base RequestMakeCommand to generate
 * form request classes within the modular structure for input validation
 * and authorization in Clean Architecture presentation layer.
 *
 * @package Kadevland\EasyModules\Commands\Make\Laravel
 * @author  Kadevland <kadevland@kaosland.net>
 * @version 1.0.0
 */
class RequestMakeCommand extends BaseRequestMakeCommand
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
    protected string $componentType = 'request';

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'easymodules:make-request';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new form request class within a module';

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
        $this->configureModuleAliases('make-request');
        parent::configure();
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
     * @param string $rootNamespace The root namespace of the application
     * @return string The default namespace for this component type in the module
     */
    protected function getDefaultNamespace($rootNamespace): string
    {
        return $this->moduleNamespace($this->getComponentType(), 'Presentation\\Http\\Requests');
    }
}
