<?php

declare(strict_types=1);

namespace Kadevland\EasyModules\Commands\Make\Laravel;

use Illuminate\Support\Str;
use Kadevland\EasyModules\Traits\HandlesModuleMakeCommands;
use Illuminate\Foundation\Console\ProviderMakeCommand as BaseProviderMakeCommand;

/**
 * Command to create service provider classes within modules
 *
 * This command extends Laravel's base ProviderMakeCommand to generate
 * service providers within the modular structure with helpful registration tips.
 *
 * @package Kadevland\EasyModules\Commands\Make\Laravel
 * @author  Kadevland <kadevland@kaosland.net>
 * @version 1.0.0
 */
class ProviderMakeCommand extends BaseProviderMakeCommand
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
    protected string $componentType = 'provider';

    /**
     * The console command name
     *
     * @var string
     */
    protected $name = 'easymodules:make-provider';

    /**
     * The console command description
     *
     * @var string
     */
    protected $description = 'Create a new service provider class within a module';

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
        $this->configureModuleAliases('make-provider');
        parent::configure();
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // COMMAND EXECUTION
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Execute the console command
     *
     * Override to provide module-specific guidance after provider creation.
     * After successfully generating the provider class, this method displays
     * helpful tips about registering the provider within the module ecosystem.
     *
     * @return mixed The result of the parent command execution
     */
    public function handle()
    {
        // Execute the standard Laravel provider generation logic
        $result = parent::handle();

        // If generation failed, return the failure result immediately
        if ($result === false) {
            return $result;
        }

        // Display helpful guidance for module provider registration
        $this->displayRegistrationTip();

        return $result;
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
        return $this->moduleNamespace($this->getComponentType(), 'Providers');
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // USER GUIDANCE METHODS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Display helpful registration tip for the created provider
     *
     * This method provides contextual guidance to help developers understand
     * how to properly register their newly created provider within the module
     * architecture, promoting best practices and reducing confusion.
     *
     * @return void
     */
    protected function displayRegistrationTip(): void
    {
        $this->info('💡 Tip: Register this provider in your module\'s main ServiceProvider if needed.');
    }
}
