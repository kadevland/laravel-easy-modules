<?php

declare(strict_types=1);

namespace Kadevland\EasyModules\Commands\Make\Laravel;

use Illuminate\Support\Composer;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Database\Migrations\MigrationCreator;
use Kadevland\EasyModules\Traits\HandlesModuleMakeCommands;
use Illuminate\Database\Console\Migrations\MigrateMakeCommand as BaseMigrateMakeCommand;

/**
 * Command to create migrations within modules.
 *
 * This command extends Laravel's base MigrateMakeCommand to generate
 * migrations within the modular structure, supporting all Laravel
 * options like --create, --table, --path, --realpath with proper module path resolution.
 *
 * @package Kadevland\EasyModules\Commands\Make\Laravel
 * @author  Kadevland <kadevland@kaosland.net>
 * @version 1.0.0
 */
class MigrateMakeCommand extends BaseMigrateMakeCommand
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
    protected string $componentType = 'migration';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'easymodules:make-migration {module : The name of the module} {name : The name of the migration}
                            {--create= : The table to be created}
                            {--table= : The table to migrate}
                            {--path= : The location where the migration file should be created}
                            {--realpath : Indicate any provided migration file paths are pre-resolved absolute paths}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new migration file within a module';

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // CONSTRUCTOR & CONFIGURATION
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Create a new migration make command instance for module context.
     *
     * This constructor manually resolves the MigrationCreator since Laravel 12
     * requires a second argument ($customStubPath) that cannot be auto-injected.
     * It ensures that migrations are generated with the correct stub path.
     *
     * @param \Illuminate\Support\Composer $composer
     *     The Composer instance used to dump the autoload after generating a migration.
     */
    public function __construct(Composer $composer)
    {
        $creator = new MigrationCreator(
            new Filesystem(),
            base_path('stubs') // Custom stub path for migration templates
        );

        parent::__construct($creator, $composer);
    }

    /**
     * Configure the command options and aliases.
     *
     * @return void
     */
    protected function configure(): void
    {
        $this->configureModuleAliases('make-migration');
        parent::configure();
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // PATH RESOLUTION METHODS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Get migration path for the module.
     *
     * Override Laravel's getMigrationPath to redirect to module directory.
     * Respects --path and --realpath options from Laravel.
     *
     * @return string The migration path
     */
    protected function getMigrationPath(): string
    {
        if (! is_null($targetPath = $this->input->getOption('path'))) {
            return ! $this->usingRealPath()
                ? $this->getModuleBasePath().'/'.$targetPath
                : $targetPath;
        }

        return $this->modulePath($this->getComponentType(), 'Database/Migrations');
    }

    /**
     * Get the module base path.
     *
     * Used to resolve relative paths with --path option.
     *
     * @return string The module base path
     */
    protected function getModuleBasePath(): string
    {
        return $this->rootModulePath();
    }

    /**
     * Check if using real path.
     *
     * Helper method for the --realpath option.
     *
     * @return bool True if using real path, false otherwise
     */
    protected function usingRealPath(): bool
    {
        return $this->input->getOption('realpath');
    }
}
