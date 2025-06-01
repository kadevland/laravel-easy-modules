<?php

declare(strict_types=1);

namespace Kadevland\EasyModules\Commands\Make\EasyModule;

use Illuminate\Support\Str;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Kadevland\EasyModules\Traits\CommandAliasManager;
use Kadevland\EasyModules\Generators\ScaffoldModuleFileGenerator;

/**
 * Command to create new module(s) with Clean Architecture structure
 *
 * This command creates the complete folder structure and essential scaffold files
 * for new modules using the easymodules:new command and its aliases. It supports
 * creating multiple modules at once and validates module names according to PHP standards.
 *
 * @package Kadevland\EasyModules\Commands\Make\EasyModule
 * @author  Kadevland <kadevland@kaosland.net>
 * @version 1.0.0
 */
class ModuleMakeCommand extends Command
{
    use CommandAliasManager;

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // PROPERTIES
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * The name and signature of the console command
     *
     * @var string
     */
    protected $signature = 'easymodules:new {name* : The names of modules to be created}';

    /**
     * The console command description
     *
     * @var string
     */
    protected $description = 'Create a new module with Clean Architecture structure';

    /**
     * The filesystem instance for file and directory operations
     *
     * @var Filesystem
     */
    protected Filesystem $files;

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // CONSTRUCTOR & CONFIGURATION
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Create a new command instance
     *
     * @param Filesystem $files The filesystem instance for directory operations
     */
    public function __construct(Filesystem $files)
    {
        $this->files = $files;
        parent::__construct();
    }

    /**
     * Configure the command options and aliases
     *
     * @return void
     */
    protected function configure(): void
    {
        $this->configureEasyModulesAliases('new');
        parent::configure();
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // MAIN EXECUTION METHODS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Execute the console command
     *
     * Main entry point that orchestrates the entire module creation process.
     * Handles multiple module creation, validation, and provides comprehensive
     * feedback to the user about the success or failure of each operation.
     *
     * @return int Command exit code (0 for success, 1 for failure)
     */
    public function handle(): int
    {
        try {
            // Validate configuration before processing arguments
            if (! $this->validateConfiguration()) {
                return Command::FAILURE;
            }

            $names          = $this->argument('name');
            $overallSuccess = true;

            // Process each module name provided
            foreach ($names as $name) {
                if (! $this->isValidModuleName($name)) {
                    $this->error("❌ [{$name}]: Module name must start with a letter and contain only letters and numbers.");
                    $overallSuccess = false;
                    continue;
                }

                $studlyName = Str::studly($name);
                $this->info("🚀 Creating module {$name} => {$studlyName}...");

                $moduleSuccess = $this->createModule($studlyName);

                if ($moduleSuccess) {
                    $this->info("✅ Module {$studlyName} created successfully!");
                } else {
                    $this->error("❌ Module {$studlyName} creation failed!");
                    $overallSuccess = false;
                }
            }

            return $overallSuccess ? Command::SUCCESS : Command::FAILURE;

        } catch (\Exception $e) {
            $this->error("❌ Unexpected error: {$e->getMessage()}");
            return Command::FAILURE;
        }
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // MODULE CREATION METHODS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Create a complete module with folders and scaffold files
     *
     * This method coordinates the creation of both the directory structure
     * and the essential scaffold files for a new module, ensuring a complete
     * and functional module setup.
     *
     * @param string $name The module name in StudlyCase format
     * @return bool True if module creation succeeded, false otherwise
     */
    protected function createModule(string $name): bool
    {
        $folderCreationSuccess   = $this->createModuleFolders($name);
        $scaffoldCreationSuccess = $this->createScaffoldFiles($name);

        return $folderCreationSuccess && $scaffoldCreationSuccess;
    }

    /**
     * Create the folder structure for a module
     *
     * Generates the complete directory tree required for a module following
     * Clean Architecture principles. Creates both standard folders and any
     * additional scaffold-specific directories defined in configuration.
     *
     * @param string $name The module name in StudlyCase format
     * @return bool True if all directories were created successfully, false otherwise
     */
    protected function createModuleFolders(string $name): bool
    {
        $modulePath = $this->getModulePath($name);

        if (! $modulePath) {
            $this->error("❌ Failed to determine module path");
            return false;
        }

        $this->info("📁 Creating directory structure...");

        $success         = true;
        $foldersToCreate = $this->getFoldersToCreate();

        foreach ($foldersToCreate as $folder) {
            $folderPath = $modulePath.DIRECTORY_SEPARATOR.$folder;

            if (! $this->createDirectory($folderPath)) {
                $this->error("❌ Failed to create directory: {$folder}");
                $success = false;
            }
        }

        return $success;
    }

    /**
     * Create scaffold files for a module
     *
     * Generates essential scaffold files (ServiceProvider, configuration files, etc.)
     * that provide the basic infrastructure needed for a functional module.
     * Uses the ScaffoldModuleFileGenerator for consistent file generation.
     *
     * @param string $name The module name in StudlyCase format
     * @return bool True if scaffold files were created successfully, false otherwise
     */
    protected function createScaffoldFiles(string $name): bool
    {
        $this->info("📄 Creating scaffold files...");

        $config    = $this->getEasyModulesConfig();
        $generator = new ScaffoldModuleFileGenerator($config, $name);

        $success = $generator->generate();

        if ($success) {
            $generated = $generator->getGeneratedFiles();
            $this->info("✅ Generated ".count($generated)." scaffold files");
        } else {
            $failed = $generator->getFailedFiles();
            foreach ($failed as $failure) {
                $this->error("❌ Failed to generate {$failure['type']}: {$failure['reason']}");
            }
        }

        return $success;
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // VALIDATION METHODS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Validate that all required configuration is present and valid
     *
     * Performs comprehensive validation of the EasyModules configuration
     * to ensure all required settings are present and the environment
     * is properly set up for module creation.
     *
     * @return bool True if configuration is valid, false otherwise
     */
    protected function validateConfiguration(): bool
    {
        $config = $this->getEasyModulesConfig();

        // Check base_path configuration
        $basePath = $config['base_path'] ?? null;
        $basePath = trim($basePath ?? '');

        if (! $basePath) {
            $this->error("❌ Base path not configured in easymodules config.");
            return false;
        }

        // Check base_namespace configuration
        $baseNamespace = $config['base_namespace'] ?? null;
        $baseNamespace = trim($baseNamespace ?? '');

        if (! $baseNamespace) {
            $this->error("❌ Base namespace not configured in easymodules config.");
            return false;
        }

        // Ensure base directory can be created and is writable
        if (! $this->ensureBaseDirectoryExists()) {
            return false;
        }

        return true;
    }

    /**
     * Validate if a module name follows the required format
     *
     * Ensures module names comply with PHP class naming conventions:
     * - Must start with a letter
     * - Can contain only letters and numbers
     * - No special characters or spaces allowed
     *
     * @param string $name The module name to validate
     * @return bool True if the name is valid, false otherwise
     */
    protected function isValidModuleName(string $name): bool
    {
        return preg_match('/^[A-Za-z][A-Za-z0-9]*$/', $name) === 1;
    }

    /**
     * Ensure the base modules directory exists and is writable
     *
     * Creates the base directory if it doesn't exist and verifies
     * that it has the proper permissions for module creation.
     *
     * @return bool True if directory exists or was created successfully
     */
    protected function ensureBaseDirectoryExists(): bool
    {
        $config   = $this->getEasyModulesConfig();
        $basePath = $config['base_path'];

        if (! $this->files->isDirectory($basePath)) {
            if (! $this->files->makeDirectory($basePath, 0755, true)) {
                $this->error("❌ Cannot create base modules directory: {$basePath}");
                return false;
            }
            $this->info("📁 Created base modules directory: {$basePath}");
        }

        if (! is_writable($basePath)) {
            $this->error("❌ Base modules directory is not writable: {$basePath}");
            return false;
        }

        return true;
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // UTILITY METHODS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Create a directory if it doesn't exist
     *
     * Safely creates a directory with proper permissions, handling
     * the case where the directory might already exist.
     *
     * @param string $path The directory path to create
     * @return bool True if directory exists or was created successfully, false otherwise
     */
    protected function createDirectory(string $path): bool
    {
        if ($this->files->isDirectory($path)) {
            return true;
        }

        return $this->files->makeDirectory($path, 0755, true, true);
    }

    /**
     * Get the complete list of folders to create for a module
     *
     * Combines folders_to_generate and scaffold folders from configuration
     * to create the complete directory structure required for a module.
     *
     * @return array<string> The list of folder paths to create
     */
    protected function getFoldersToCreate(): array
    {
        $config = $this->getEasyModulesConfig();

        return array_merge(
            $config['folders_to_generate'] ?? [],
            $config['scaffold'] ?? []
        );
    }

    /**
     * Get the full path where the module will be created
     *
     * Constructs the complete filesystem path for a module based on
     * the configured base path and the module name.
     *
     * @param string $name The module name in StudlyCase format
     * @return string|null The full path to the module, or null if base path is not configured
     */
    protected function getModulePath(string $name): ?string
    {
        $config   = $this->getEasyModulesConfig();
        $basePath = $config['base_path'] ?? null;
        $basePath = trim($basePath ?? '');

        if (! $basePath) {
            return null;
        }

        return $basePath.DIRECTORY_SEPARATOR.$name;
    }

    /**
     * Get the easy-modules configuration
     *
     * Retrieves the complete EasyModules configuration array from
     * the Laravel configuration system.
     *
     * @return array<string, mixed> The configuration array
     */
    protected function getEasyModulesConfig(): array
    {
        return $this->laravel['config']->get('easymodules', []);
    }
}
