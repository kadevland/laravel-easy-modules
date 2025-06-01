<?php

declare(strict_types=1);

namespace Kadevland\EasyModules\Commands\Make\Laravel;

use Illuminate\Support\Str;
use Kadevland\EasyModules\Traits\HandlesModuleMakeCommands;
use Illuminate\Database\Console\Factories\FactoryMakeCommand as BaseFactoryMakeCommand;

/**
 * Command to create model factories within modules
 *
 * This command extends Laravel's base FactoryMakeCommand to generate
 * model factories within the modular structure, supporting model
 * resolution and proper namespace handling.
 *
 * @package Kadevland\EasyModules\Commands\Make\Laravel
 * @author  Kadevland <kadevland@kaosland.net>
 * @version 1.0.0
 */
class FactoryMakeCommand extends BaseFactoryMakeCommand
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
    protected string $componentType = 'factory';

    /**
     * The console command name
     *
     * @var string
     */
    protected $name = 'easymodules:make-factory';

    /**
     * The console command description
     *
     * @var string
     */
    protected $description = 'Create a new model factory within a module';

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
        $this->configureModuleAliases('make-factory');
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
        return $this->moduleNamespace($this->getComponentType(), 'Database\\Factories');
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // MODEL INTEGRATION METHODS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Replace namespace and handle model resolution in module context
     *
     * This method processes the stub template and replaces model-related
     * placeholders with the appropriate module-aware values. It intelligently
     * resolves model names either from the --model option or by convention
     * from the factory name, then constructs the proper module namespace.
     *
     * @param string $stub The stub content to process
     * @param string $name The fully qualified factory class name
     * @return mixed The result of parent namespace replacement
     */
    protected function replaceNamespace(&$stub, $name)
    {
        // Get the factory namespace for stub replacement
        $factoryNamespace = $this->getNamespace($name);

        // Determine the model name from option or factory name convention
        if ($this->option('model')) {
            // Use explicitly provided model name
            $modelName = $this->option('model');
        } else {
            // Derive model name from factory name by removing 'Factory' suffix
            $fullFactoryName = str_replace('Factory', '', $this->getNameInput());
            $modelName       = str_replace('/', '\\', $fullFactoryName);
        }

        // Construct the full model class path within the module
        $modelClass = $this->moduleNamespace('model', 'Infrastructure\\Models').'\\'.$modelName;

        // Define all placeholder replacements for the stub
        $searches = [
            '{{ factoryNamespace }}' => $factoryNamespace,
            '{{factoryNamespace}}'   => $factoryNamespace,
            '{{ namespacedModel }}'  => $modelClass,
            '{{namespacedModel}}'    => $modelClass,
        ];

        // Apply replacements to the stub content
        $stub = str_replace(array_keys($searches), array_values($searches), $stub);

        // Delegate to parent for standard Laravel namespace processing
        return parent::replaceNamespace($stub, $name);
    }
}
