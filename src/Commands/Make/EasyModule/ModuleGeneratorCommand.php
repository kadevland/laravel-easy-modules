<?php

declare(strict_types=1);

namespace Kadevland\EasyModules\Commands\Make\EasyModule;

use Illuminate\Support\Str;
use Illuminate\Console\Command;
use Illuminate\Console\GeneratorCommand;
use Kadevland\EasyModules\Traits\HandlesModuleMakeCommands;

/**
 * Base class for all module make commands.
 *
 * This abstract class provides the common functionality for all module
 * make commands, reducing code duplication and ensuring consistency.
 * It handles path resolution, namespace generation, and stub processing
 * for modular architecture components.
 *
 * @package Kadevland\EasyModules\Commands\Make\EasyModule
 * @author  Kadevland <kadevland@kaosland.net>
 * @version 1.0.0
 * @since   1.0.0
 *
 * @abstract
 */
abstract class ModuleGeneratorCommand extends GeneratorCommand
{
    use HandlesModuleMakeCommands;

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // ABSTRACT PROPERTIES (Must be defined in child classes)
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * The component type for this command (must be defined in child classes).
     *
     * @var string
     */
    protected string $componentType;

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // CONFIGURATION
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Configure the command with aliases.
     *
     * This method sets up command aliases and calls the parent configure method.
     * It should be called during command initialization.
     *
     * @return void
     */
    protected function configure(): void
    {
        $commandName = $this->getCommandBaseName();
        $this->configureModuleAliases($commandName);
        parent::configure();
    }

    /**
     * Get the command base name for alias configuration.
     *
     * This method generates the base command name used for creating
     * command aliases based on the component type.
     *
     * @return string The base command name
     */
    protected function getCommandBaseName(): string
    {
        return "make-{$this->getComponentType()}";
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // CORE GENERATOR METHODS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Get the stub file for the generator.
     *
     * This method resolves the path to the stub file that will be used
     * as a template for generating the new component.
     *
     * @return string The full path to the stub file
     * @throws \RuntimeException If the stub file cannot be found
     */
    protected function getStub(): string
    {
        return $this->resolveStubPath($this->getComponentType());
    }

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
     * This method determines the namespace that should be used for
     * the generated class based on the component type and module structure.
     *
     * @param string $rootNamespace The root namespace of the application
     * @return string The default namespace for this component type
     */
    protected function getDefaultNamespace($rootNamespace): string
    {
        $configPath = $this->laravel['config']->get("easymodules.paths.{$this->getComponentType()}");

        return $configPath
            ? $this->moduleNamespace($this->getComponentType(), str_replace('/', '\\', $configPath))
            : $this->rootModuleNamespace(); // Fallback
    }

    /**
     * Build the class with the given name.
     *
     * This method processes the stub template and replaces placeholders
     * with actual values. Child classes can override this method to add
     * custom replacements specific to their component type.
     *
     * @param string $name The fully qualified class name
     * @return string The processed class content
     */
    protected function buildClass($name): string
    {
        $replace = $this->buildReplacements();

        return str_replace(
            array_keys($replace),
            array_values($replace),
            parent::buildClass($name)
        );
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // COMPONENT TYPE & PATH RESOLUTION
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Get the component type for configuration lookup.
     *
     * @return string The component type (must be defined in child classes)
     */
    protected function getComponentType(): string
    {
        return $this->componentType;
    }

    /**
     * Get the default path for this component type.
     *
     * This method returns the default directory path where components
     * of this type should be placed within the module structure.
     * Child classes can override this method if they need custom paths.
     *
     * @return string The default path for this component type
     */
    protected function getDefaultComponentPath(): string
    {
        return '';

        // $defaultPaths = [
        //     // Domain Layer
        //     'entity'        => 'Domain/Entities',
        //     'valueobject'   => 'Domain/ValueObjects',
        //     'domainservice' => 'Domain/Services',

        //     // Application Layer
        //     'dto'           => 'Application/DTOs',
        //     'mapper'        => 'Application/Mappers',
        //     'action'        => 'Application/Actions',
        //     'interface'     => 'Application/Interfaces',
        //     'validation'    => 'Application/Validation',
        //     'rule'          => 'Application/Rules',

        //     // Infrastructure Layer
        //     'model'         => 'Infrastructure/Models',
        //     'repository'    => 'Infrastructure/Persistence/Repositories',
        //     'persistence'   => 'Infrastructure/Persistence',
        //     'cast'          => 'Infrastructure/Casts',
        //     'job'           => 'Infrastructure/Jobs',
        //     'event'         => 'Infrastructure/Events',
        //     'listener'      => 'Infrastructure/Listeners',
        //     'mail'          => 'Infrastructure/Mail',
        //     'notification'  => 'Infrastructure/Notifications',
        //     'policy'        => 'Infrastructure/Policies',
        //     'observer'      => 'Infrastructure/Observers',

        //     // Presentation Layer
        //     'controller'    => 'Presentation/Http/Controllers',
        //     'request'       => 'Presentation/Http/Requests',
        //     'middleware'    => 'Presentation/Http/Middleware',
        //     'resource'      => 'Presentation/Http/Resources',
        //     'command'       => 'Presentation/Console/Commands',
        //     'component'     => 'Presentation/Views/Components',

        //     // Database Layer
        //     'migration'     => 'Database/Migrations',
        //     'seeder'        => 'Database/Seeders',
        //     'factory'       => 'Database/Factories',

        //     // Testing
        //     'test'          => 'Tests/Unit',
        //     'featuretest'   => 'Tests/Feature',
        // ];

        // return $defaultPaths[$this->getComponentType()] ?? '';
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // STUB REPLACEMENT SYSTEM
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Build replacement variables for the stub.
     *
     * This method creates an array of placeholder replacements that will
     * be used to customize the stub template. Child classes should override
     * this method to add specific replacements for their component type.
     *
     * @return array<string, string> Array of placeholder replacements
     */
    protected function buildReplacements(): array
    {
        return [
            '{{ module }}'       => $this->getModuleInput(),
            '{{ module_lower }}' => Str::lower($this->getModuleInput()),
            '{{ module_snake }}' => Str::snake($this->getModuleInput()),
            '{{ type }}'         => $this->getComponentType(),
            '{{ type_studly }}'  => Str::studly($this->getComponentType()),
        ];
    }

    public function handle(): int
    {

        return parent::handle() !== false ? Command::SUCCESS : Command::FAILURE;
    }
}
