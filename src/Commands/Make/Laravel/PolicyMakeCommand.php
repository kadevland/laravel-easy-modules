<?php

declare(strict_types=1);

namespace Kadevland\EasyModules\Commands\Make\Laravel;

use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputOption;
use Kadevland\EasyModules\Traits\ParsesModuleModels;
use Kadevland\EasyModules\Traits\HandlesModuleMakeCommands;
use Illuminate\Foundation\Console\PolicyMakeCommand as BasePolicyMakeCommand;

/**
 * Command to create policy classes within modules.
 *
 * This command extends Laravel's base PolicyMakeCommand to generate
 * policies within the modular structure, supporting ALL Laravel options:
 * --force, --model, --guard, with intelligent model resolution within modules.
 *
 * @package Kadevland\EasyModules\Commands\Make\Laravel
 * @author  Kadevland <kadevland@kaosland.net>
 * @version 1.0.0
 */
class PolicyMakeCommand extends BasePolicyMakeCommand
{
    use HandlesModuleMakeCommands, ParsesModuleModels;

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // PROPERTIES
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * The component type for this command.
     *
     * @var string
     */
    protected string $componentType = 'policy';

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'easymodules:make-policy';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new policy class within a module';

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
        $this->configureModuleAliases('make-policy');
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
            ['include-global', 'ig', InputOption::VALUE_NONE, 'Include global model in suggestions'],
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
     * @param string $rootNamespace The root namespace of the application
     * @return string The default namespace for this component type in the module
     */
    protected function getDefaultNamespace($rootNamespace): string
    {
        return $this->moduleNamespace($this->getComponentType(), 'Infrastructure\\Policies');
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // MODEL INTEGRATION METHODS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Replace the model for the given stub.
     *
     * Uses module-aware model parsing to resolve the correct model class
     * within the modular architecture.
     *
     * @param string $stub The stub content
     * @param string $model The model name
     * @return string The processed stub content
     */
    protected function replaceModel($stub, $model)
    {
        return parent::replaceModel($stub, $this->parseModel($model));
    }


    /**
     * Get all possible model class names
     *
     * Override to include module models in suggestions with optional global models.
     * This method provides intelligent model discovery following these rules:
     *
     * - **Default behavior**: Only models from the current module
     * - **With --include-global**: Current module models + Laravel global models (App/Models)
     * - **Future enhancement**: Other modules not included yet (planned --include-module option)
     *
     * This approach ensures proper module boundaries while providing flexibility
     * to work with global Laravel models when explicitly requested.
     *
     * @return array Array of possible model names for autocomplete and validation
     */
    protected function possibleModels(): array
    {
        return $this->getPossibleComponents('model',
            $this->option('include-global') ? fn () => parent::possibleModels() : null
        );
    }
}
