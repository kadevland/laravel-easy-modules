<?php

declare(strict_types=1);

namespace Kadevland\EasyModules\Commands\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Illuminate\Filesystem\Filesystem;
use Kadevland\EasyModules\Traits\CommandAliasManager;

/**
 * Command to display module structure and registration information.
 *
 * Shows the relationship between configured structure and actual folders,
 * registration status, and basic module information.
 *
 * @package Kadevland\EasyModules\Commands\Console
 * @author  Kadevland <kadevland@kaosland.net>
 * @version 1.0.0
 */
class InfoModulesCommand extends Command
{
    use CommandAliasManager;

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // PROPERTIES
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'easymodules:info
                        {module? : The name of the module to inspect}
                        {--all : Show information for all modules}
                        {--json : Output as JSON format}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Display detailed module information and structure';

    /**
     * EasyModules configuration properties.
     */
    protected string $basePath;
    protected string $baseNamespace;
    protected array  $paths;
    protected array  $testPaths;
    protected array  $scaffoldFolders;
    protected array  $configuredFolders;

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // CONSTRUCTOR & CONFIGURATION
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Create a new command instance.
     *
     * @param Filesystem $files
     */
    public function __construct(protected Filesystem $files)
    {
        parent::__construct();
        $this->initializeConfigProperties();
    }

    /**
     * Configure the command options and aliases.
     *
     * @return void
     */
    protected function configure(): void
    {
        $this->configureEasyModulesAliases('info');
        parent::configure();
    }

    /**
     * Initialize configuration properties.
     *
     * @return void
     */
    protected function initializeConfigProperties(): void
    {
        $this->basePath          = config('easymodules.base_path', app_path('Modules'));
        $this->baseNamespace     = config('easymodules.base_namespace', 'App\\Modules');
        $this->paths             = config('easymodules.paths', []);
        $this->testPaths         = config('easymodules.test_paths', []);
        $this->scaffoldFolders   = config('easymodules.scaffold', ['Providers', 'config', 'routes']);
        $this->configuredFolders = config('easymodules.folders_to_generate', []);
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // PUBLIC COMMAND EXECUTION
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Execute the console command.
     *
     * @return int Command exit code
     */
    public function handle(): int
    {
        $moduleName = $this->argument('module');
        $showAll    = $this->option('all');

        if (! $moduleName && ! $showAll) {
            $this->error('❌ Please specify a module name or use --all option.');
            $this->line('Examples:');
            $this->line('  php artisan easymodules:info Blog');
            $this->line('  php artisan easymodules:info --all');
            return Command::FAILURE;
        }

        if ($showAll) {
            return $this->handleAllModules();
        }

        return $this->handleSingleModule($moduleName);
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // MAIN HANDLERS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Handle information display for all modules.
     *
     * @return int Command exit code
     */
    protected function handleAllModules(): int
    {
        $modules = $this->discoverAllModules();

        if (empty($modules)) {
            if ($this->option('json')) {
                $this->line('[]');
                return Command::SUCCESS;
            }
            $this->warn('No modules found.');
            return Command::SUCCESS;
        }

        if ($this->option('json')) {
            $allModulesData = [];
            foreach ($modules as $moduleData) {
                $allModulesData[] = $this->getModuleData($moduleData['name']);
            }
            $this->line(json_encode($allModulesData, JSON_PRETTY_PRINT));
            return Command::SUCCESS;
        }

        $this->info('📋 Information for All Modules');
        $this->newLine();

        foreach ($modules as $index => $moduleData) {
            if ($index > 0) {
                $this->newLine();
                $this->line(str_repeat('─', 60));
                $this->newLine();
            }
            $this->handleSingleModule($moduleData['name']);
        }

        return Command::SUCCESS;
    }

    /**
     * Handle information display for a single module.
     *
     * @param string $moduleName The module name
     * @return int Command exit code
     */
    protected function handleSingleModule(string $moduleName): int
    {
        $moduleName = Str::studly($moduleName);

        if (! $this->moduleExists($moduleName)) {
            if ($this->option('json')) {
                $this->line(json_encode(['error' => "Module '{$moduleName}' not found."], JSON_PRETTY_PRINT));
                return Command::FAILURE;
            }
            $this->error("❌ Module '{$moduleName}' not found.");
            return Command::FAILURE;
        }

        if ($this->option('json')) {
            $moduleData = $this->getModuleData($moduleName);
            $this->line(json_encode($moduleData, JSON_PRETTY_PRINT));
            return Command::SUCCESS;
        }

        $this->displayModuleInfo($moduleName);
        return Command::SUCCESS;
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // DATA PROCESSING & ANALYSIS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Get module data with folder classification algorithm.
     *
     * @param string $moduleName The module name
     * @return array Module data for JSON or display
     */
    protected function getModuleData(string $moduleName): array
    {
        $modulePath       = $this->getModulePath($moduleName);
        $relativeBasePath = str_replace(base_path(), '', $this->basePath);

        $allFolders = $this->getAllExistingFolders($modulePath);

        $folderClassification = $this->classifyFolders($allFolders);
        $registration         = $this->getRegistrationData($moduleName, $modulePath);

        return [
            'name'         => $moduleName,
            'base_path'    => "{$relativeBasePath}/{$moduleName}",
            'namespace'    => $this->getModuleNamespace($moduleName),
            'exists'       => $this->files->isDirectory($modulePath),
            'folders'      => [
                'all_existing'       => $allFolders,
                'scaffold'           => $folderClassification['scaffold'],
                'generated'          => $folderClassification['generated'],
                'paths'              => $folderClassification['paths'],
                'test_paths'         => $folderClassification['test_paths'],
                'additional'         => $folderClassification['additional'],
                'missing_scaffold'   => $folderClassification['missing_scaffold'],
                'missing_generated'  => $folderClassification['missing_generated'],
                'missing_paths'      => $folderClassification['missing_paths'],
                'missing_test_paths' => $folderClassification['missing_test_paths'],
            ],
            'registration' => $registration,
        ];
    }

    /**
     * Get all existing folders in the module directory.
     * Extracted for testability - can be mocked in unit tests.
     *
     * @param string $modulePath The module path
     * @return array List of existing folder names
     */
    protected function getAllExistingFolders(string $modulePath): array
    {
        $allFolders = [];

        if ($this->files->isDirectory($modulePath)) {
            $directories = $this->files->directories($modulePath);
            foreach ($directories as $directory) {
                $allFolders[] = basename($directory);
            }
            sort($allFolders);
        }

        return $allFolders;
    }

    /**
     * Classify folders according to EasyModule configuration sources.
     *
     * @param array $existingFolders List of existing folder names
     * @return array Classification collections
     */
    protected function classifyFolders(array $existingFolders): array
    {
        $scaffoldFolders  = $this->scaffoldFolders;
        $generatedFolders = $this->configuredFolders;
        $pathFolders      = array_values($this->paths);
        $testPathFolders  = array_values($this->testPaths);

        $classification = [
            'scaffold'           => [],
            'generated'          => [],
            'paths'              => [],
            'test_paths'         => [],
            'additional'         => [],
            'missing_scaffold'   => [],
            'missing_generated'  => [],
            'missing_paths'      => [],
            'missing_test_paths' => [],
        ];

        foreach ($existingFolders as $folder) {
            $classified = false;

            if (in_array($folder, $scaffoldFolders)) {
                $classification['scaffold'][] = $folder;
                $classified                   = true;
            } elseif (in_array($folder, $generatedFolders)) {
                $classification['generated'][] = $folder;
                $classified                    = true;
            } elseif (in_array($folder, $pathFolders)) {
                $classification['paths'][] = $folder;
                $classified                = true;
            } elseif (in_array($folder, $testPathFolders)) {
                $classification['test_paths'][] = $folder;
                $classified                     = true;
            }

            if (! $classified) {
                $classification['additional'][] = $folder;
            }
        }

        foreach ($scaffoldFolders as $folder) {
            if (! in_array($folder, $existingFolders)) {
                $classification['missing_scaffold'][] = $folder;
            }
        }

        foreach ($generatedFolders as $folder) {
            if (! in_array($folder, $existingFolders)) {
                $classification['missing_generated'][] = $folder;
            }
        }

        foreach ($pathFolders as $folder) {
            if (! in_array($folder, $existingFolders)) {
                $classification['missing_paths'][] = $folder;
            }
        }

        foreach ($testPathFolders as $folder) {
            if (! in_array($folder, $existingFolders)) {
                $classification['missing_test_paths'][] = $folder;
            }
        }

        return $classification;
    }

    /**
     * Get registration data for the module.
     *
     * @param string $moduleName The module name
     * @param string $modulePath The module path
     * @return array Registration data
     */
    protected function getRegistrationData(string $moduleName, string $modulePath): array
    {
        $providerExists     = $this->hasServiceProvider($modulePath, $moduleName);
        $providerRegistered = $this->isServiceProviderRegistered($moduleName);
        $routes             = $this->getRoutesInfo($modulePath);

        return [
            'service_provider' => [
                'class'      => "{$moduleName}ServiceProvider",
                'exists'     => $providerExists,
                'registered' => $providerRegistered,
            ],
            'routes'           => $routes,
        ];
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // DISPLAY METHODS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Display module information using consistent data source.
     *
     * @param string $moduleName The module name
     * @return void
     */
    protected function displayModuleInfo(string $moduleName): void
    {
        $moduleData = $this->getModuleData($moduleName);

        $this->info("📁 Module: {$moduleData['name']}");
        $this->line("📍 Base Path: {$moduleData['base_path']}");
        $this->line("🔧 Namespace: {$moduleData['namespace']}");
        $this->newLine();

        $this->displayFolderSection('🏗️ Scaffold Structure:', $moduleData['folders']['scaffold'], $moduleData['folders']['missing_scaffold']);
        $this->displayFolderSection('📁 Generated Structure:', $moduleData['folders']['generated'], $moduleData['folders']['missing_generated']);

        if (! empty($moduleData['folders']['paths']) || ! empty($moduleData['folders']['missing_paths'])) {
            $this->displayFolderSection('🛠️ Paths Structure:', $moduleData['folders']['paths'], $moduleData['folders']['missing_paths']);
        }

        if (! empty($moduleData['folders']['test_paths']) || ! empty($moduleData['folders']['missing_test_paths'])) {
            $this->displayFolderSection('🧪 Test Paths Structure:', $moduleData['folders']['test_paths'], $moduleData['folders']['missing_test_paths']);
        }

        if (! empty($moduleData['folders']['additional'])) {
            $this->line('📄 Additional Structures (not in config):');
            foreach ($moduleData['folders']['additional'] as $folder) {
                $this->line("  <info>✅</info> {$folder}/");
            }
            $this->newLine();
        }

        $this->displayRegistrationInfo($moduleData['registration']);
    }

    /**
     * Display a folder section with existing and missing folders.
     *
     * @param string $title Section title
     * @param array $existingFolders Existing folders in this category
     * @param array $missingFolders Missing folders in this category
     * @return void
     */
    protected function displayFolderSection(string $title, array $existingFolders, array $missingFolders): void
    {
        $this->line($title);

        if (empty($existingFolders) && empty($missingFolders)) {
            $this->line('  <comment>No folders configured for this category</comment>');
            $this->newLine();
            return;
        }

        foreach ($existingFolders as $folder) {
            $this->line("  <info>✅</info> {$folder}");
        }

        foreach ($missingFolders as $folder) {
            $this->line("  <comment>❌</comment> {$folder}");
        }

        $this->newLine();
    }

    /**
     * Display registration information.
     *
     * @param array $registration Registration data
     * @return void
     */
    protected function displayRegistrationInfo(array $registration): void
    {
        $this->line('🔧 Registration:');

        $provider       = $registration['service_provider'];
        $providerStatus = $provider['exists'] ?
            ($provider['registered'] ? '(registered)' : '(not registered)') :
            '(missing)';

        $providerIcon = $provider['exists'] && $provider['registered'] ? '<info>✅</info>' : '<comment>❌</comment>';
        $this->line("  {$providerIcon} ServiceProvider: {$provider['class']} {$providerStatus}");

        $routes       = $registration['routes'];
        $routesStatus = [];

        foreach ($routes as $type => $exists) {
            $routesStatus[] = $exists ? "<info>✅ {$type}.php</info>" : "<comment>❌ {$type}.php</comment>";
        }

        $this->line('  Routes: '.implode(', ', $routesStatus));
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // MODULE DISCOVERY
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Discover all available modules.
     *
     * @return array All discovered modules
     */
    protected function discoverAllModules(): array
    {
        if (! $this->files->isDirectory($this->basePath)) {
            return [];
        }

        $modules     = [];
        $directories = $this->files->directories($this->basePath);

        foreach ($directories as $directory) {
            $moduleName = basename($directory);

            if ($this->hasServiceProvider($directory, $moduleName)) {
                $modules[] = [
                    'name' => $moduleName,
                    'path' => $directory,
                ];
            }
        }

        usort($modules, fn ($a, $b) => strcmp($a['name'], $b['name']));
        return $modules;
    }

    /**
     * Check if a module exists.
     *
     * @param string $moduleName The module name
     * @return bool True if module exists
     */
    protected function moduleExists(string $moduleName): bool
    {
        $modulePath = $this->getModulePath($moduleName);
        return $this->files->isDirectory($modulePath) && $this->hasServiceProvider($modulePath, $moduleName);
    }

    /**
     * Check if a module has a ServiceProvider.
     *
     * @param string $modulePath The module directory path
     * @param string $moduleName The module name
     * @return bool True if ServiceProvider exists
     */
    protected function hasServiceProvider(string $modulePath, string $moduleName): bool
    {
        $providerDir  = $this->paths['provider'] ?? 'Providers';
        $providerPath = $modulePath.'/'.$providerDir.'/'.$moduleName.'ServiceProvider.php';
        return $this->files->exists($providerPath);
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // SERVICE PROVIDER REGISTRATION CHECKS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Check if ServiceProvider is registered in bootstrap/providers.php.
     *
     * @param string $moduleName The module name
     * @return bool True if registered
     */
    protected function isServiceProviderRegistered(string $moduleName): bool
    {
        $providersFile = app()->getBootstrapProvidersPath();

        if (! $this->files->exists($providersFile)) {
            return false;
        }

        $content       = $this->getBootstrapProvidersContent($providersFile);
        $providerClass = $this->getServiceProviderClass($moduleName);

        $fullClassName = $providerClass;
        $baseClassName = class_basename($providerClass);

        return $this->isProviderInContent($content, $fullClassName, $baseClassName);
    }

    /**
     * Get the content of bootstrap/providers.php file.
     * Separated for testing purposes.
     *
     * @param string $providersFile Path to providers file
     * @return string File content
     */
    protected function getBootstrapProvidersContent(string $providersFile): string
    {
        return $this->files->get($providersFile);
    }

    /**
     * Check if provider is present in content using multiple detection methods.
     *
     * @param string $content File content
     * @param string $fullClassName Full namespace class name
     * @param string $baseClassName Base class name only
     * @return bool True if found
     */
    protected function isProviderInContent(string $content, string $fullClassName, string $baseClassName): bool
    {
        if (str_contains($content, $fullClassName.'::class')) {
            return true;
        }

        if ($this->hasUseStatementAndClass($content, $fullClassName, $baseClassName)) {
            return true;
        }

        if (str_contains($content, "'{$fullClassName}'") || str_contains($content, "\"{$fullClassName}\"")) {
            return true;
        }

        return false;
    }

    /**
     * Check if content has both use statement and basename::class.
     *
     * @param string $content File content
     * @param string $fullClassName Full namespace class name
     * @param string $baseClassName Base class name only
     * @return bool True if both use statement and class reference found
     */
    protected function hasUseStatementAndClass(string $content, string $fullClassName, string $baseClassName): bool
    {
        $usePatterns = [
            "use {$fullClassName};",
            "use {$fullClassName} ",
        ];

        $hasUseStatement = false;
        foreach ($usePatterns as $pattern) {
            if (str_contains($content, $pattern)) {
                $hasUseStatement = true;
                break;
            }
        }

        if (! $hasUseStatement) {
            return false;
        }

        $classPatterns = [
            "{$baseClassName}::class",
            "'{$baseClassName}'",
            "\"{$baseClassName}\"",
        ];

        foreach ($classPatterns as $pattern) {
            if (str_contains($content, $pattern)) {
                return true;
            }
        }

        return false;
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // UTILITY METHODS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Get the full path for a module.
     *
     * @param string $moduleName The module name
     * @return string The full module path
     */
    protected function getModulePath(string $moduleName): string
    {
        return $this->basePath.'/'.$moduleName;
    }

    /**
     * Get the module namespace.
     *
     * @param string $moduleName The module name
     * @return string The module namespace
     */
    protected function getModuleNamespace(string $moduleName): string
    {
        return $this->baseNamespace.'\\'.$moduleName;
    }

    /**
     * Get the fully qualified ServiceProvider class name.
     *
     * @param string $moduleName The module name
     * @return string The ServiceProvider class name
     */
    protected function getServiceProviderClass(string $moduleName): string
    {
        $providerPath      = $this->paths['provider'] ?? 'Providers';
        $providerNamespace = str_replace('/', '\\', $providerPath);

        return $this->baseNamespace.'\\'.$moduleName.'\\'.$providerNamespace.'\\'.$moduleName.'ServiceProvider';
    }

    /**
     * Check if a module has route files and return details.
     *
     * @param string $modulePath The module directory path
     * @return array Routes information
     */
    protected function getRoutesInfo(string $modulePath): array
    {
        $routesPath = $modulePath.'/'.($this->paths['routes'] ?? 'routes');

        return [
            'web'     => $this->files->exists($routesPath.'/web.php'),
            'api'     => $this->files->exists($routesPath.'/api.php'),
            'console' => $this->files->exists($routesPath.'/console.php'),
        ];
    }
}
