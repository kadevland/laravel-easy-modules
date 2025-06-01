<?php

declare(strict_types=1);

namespace Kadevland\EasyModules\Commands\Make\Laravel;

use Illuminate\Support\Str;
use Kadevland\EasyModules\Traits\HandlesModuleMakeCommands;
use Illuminate\Foundation\Console\ModelMakeCommand as BaseModelMakeCommand;

/**
 * Command to create Eloquent models within modules.
 *
 * This command extends Laravel's base ModelMakeCommand to generate
 * models within the modular structure, supporting ALL Laravel options:
 * --all, --controller, --factory, --migration, --policy, --seed, --pivot,
 * --resource, --api, --requests, --test, --pest, --morph-pivot, --force
 *
 * @package Kadevland\EasyModules\Commands\Make\Laravel
 * @author  Kadevland <kadevland@kaosland.net>
 * @version 1.0.0
 */
class ModelMakeCommand extends BaseModelMakeCommand
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
    protected string $componentType = 'model';

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'easymodules:make-model';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new Eloquent model class within a module';

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
        $this->configureModuleAliases('make-model');
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
        return $this->moduleNamespace($this->getComponentType(), 'Infrastructure\\Models');
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // RELATED COMPONENT CREATION METHODS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Create a model factory for the model.
     *
     * Redirects to easymodules:make-factory instead of make:factory
     *
     * @return void
     */
    protected function createFactory(): void
    {
        $factory = Str::studly($this->argument('name'));

        $this->call('easymodules:make-factory', [
            'module'  => $this->getModuleInput(),
            'name'    => "{$factory}Factory",
            '--model' => $this->qualifyClass($this->getNameInput()),
        ]);
    }

    /**
     * Create a migration file for the model.
     *
     * Redirects to easymodules:make-migration instead of make:migration
     *
     * @return void
     */
    protected function createMigration(): void
    {
        $table = Str::snake(Str::pluralStudly(class_basename($this->argument('name'))));

        if ($this->option('pivot')) {
            $table = Str::singular($table);
        }

        $this->call('easymodules:make-migration', [
            'module'   => $this->getModuleInput(),
            'name'     => "create_{$table}_table",
            '--create' => $table,
        ]);
    }

    /**
     * Create a seeder file for the model.
     *
     * Redirects to easymodules:make-seeder instead of make:seeder
     *
     * @return void
     */
    protected function createSeeder(): void
    {
        $seeder = Str::studly(class_basename($this->argument('name')));

        $this->call('easymodules:make-seeder', [
            'module' => $this->getModuleInput(),
            'name'   => "{$seeder}Seeder",
        ]);
    }

    /**
     * Create a controller for the model.
     *
     * Redirects to easymodules:make-controller instead of make:controller
     * Preserves ALL Laravel options (--model, --api, --requests, --test, --pest)
     *
     * @return void
     */
    protected function createController(): void
    {
        $controller = Str::studly(class_basename($this->argument('name')));
        $modelName  = $this->getNameInput();

        $this->call('easymodules:make-controller', array_filter([
            'module'     => $this->getModuleInput(),
            'name'       => "{$controller}Controller",
            '--model'    => $this->option('resource') || $this->option('api') ? $modelName : null,
            '--api'      => $this->option('api'),
            '--requests' => $this->option('requests') || $this->option('all'),
            '--test'     => $this->option('test'),
            '--pest'     => $this->option('pest'),
        ]));
    }

    /**
     * Create the form requests for the model.
     *
     * Redirects to easymodules:make-request instead of make:request
     *
     * @return void
     */
    protected function createFormRequests(): void
    {
        $request = Str::studly(class_basename($this->argument('name')));

        $this->call('easymodules:make-request', [
            'module' => $this->getModuleInput(),
            'name'   => "Store{$request}Request",
        ]);

        $this->call('easymodules:make-request', [
            'module' => $this->getModuleInput(),
            'name'   => "Update{$request}Request",
        ]);
    }

    /**
     * Create a policy file for the model.
     *
     * Redirects to easymodules:make-policy instead of make:policy
     *
     * @return void
     */
    protected function createPolicy(): void
    {
        $policy = Str::studly(class_basename($this->argument('name')));

        $this->call('easymodules:make-policy', [
            'module'  => $this->getModuleInput(),
            'name'    => "{$policy}Policy",
            '--model' => $this->getNameInput(),
        ]);
    }
}
