<?php

declare(strict_types=1);

namespace Kadevland\EasyModules\Commands\Make\Laravel;

use Illuminate\Support\Str;
use Illuminate\Support\Stringable;
use Kadevland\EasyModules\Traits\HandlesModuleMakeCommands;
use Illuminate\Foundation\Console\ConsoleMakeCommand as BaseConsoleMakeCommand;

/**
 * Command to create console commands within modules
 *
 * This command extends Laravel's base ConsoleMakeCommand to generate
 * Artisan commands within the modular structure, supporting all Laravel
 * options like --command.
 *
 * @package Kadevland\EasyModules\Commands\Make\Laravel
 * @author  Kadevland <kadevland@kaosland.net>
 * @version 1.0.0
 */
class ConsoleMakeCommand extends BaseConsoleMakeCommand
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
    protected string $componentType = 'command';

    /**
     * The console command name
     *
     * @var string
     */
    protected $name = 'easymodules:make-command';

    /**
     * The console command description
     *
     * @var string
     */
    protected $description = 'Create a new Artisan command within a module';

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
        $this->configureModuleAliases('make-command');
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
        return $this->moduleNamespace($this->getComponentType(), 'Presentation\\Console\\Commands');
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // STUB REPLACEMENT METHODS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Replace the class name for the given stub.
     *
     * Override to use module-scoped command names.
     *
     * @param string $stub The stub content to modify
     * @param string $name The class name
     * @return string The modified stub content
     */
    protected function replaceClass($stub, $name): string
    {
        $moduleName = Str::lower($this->getModuleInput());
        $command    = $this->option('command') ?: $moduleName.':'.(new Stringable($name))->classBasename()
            ->kebab()
            ->value();

        $stub = str_replace(['dummy:command', '{{ command }}'], $command, $stub);

        return parent::replaceClass($stub, $name);
    }
}
