<?php

declare(strict_types=1);

namespace Kadevland\EasyModules\Commands\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Kadevland\EasyModules\Traits\CommandAliasManager;

/**
 * Command to list all discovered modules and their information.
 *
 * This command scans the modules directory and displays information about
 * each discovered module including their provider and routes information.
 *
 * @package Kadevland\EasyModules\Commands\Console
 * @author  Kadevland <kadevland@kaosland.net>
 * @version 1.0.0
 */
class ListModulesCommand extends Command
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
    protected $signature = 'easymodules:list
                        {--routes : Show modules with routes information}
                        {--json : Output as JSON format}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List all discovered modules with detailed information';

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
        $this->configureEasyModulesAliases('list', []);
        parent::configure();
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
        $modules = $this->discoverModules();

        // Handle JSON output mode
        if ($this->option('json')) {
            if (empty($modules)) {
                $this->line('[]');
            } else {
                $this->line(json_encode(value: $modules, flags: JSON_PRETTY_PRINT));
            }
            return Command::SUCCESS;
        }

        // Normal text output mode
        $this->displayHeader();

        if (empty($modules)) {
            $this->displayNoModulesFound();
            return Command::SUCCESS;
        }

        $this->displayModulesTable($modules);
        $this->displaySummary($modules);
        return Command::SUCCESS;
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // MODULE DISCOVERY
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Discover all available modules.
     *
     * @return array<array{name: string, path: string, provider: string, routes: array}>
     */
    protected function discoverModules(): array
    {
        $basePath = config('easymodules.base_path', app_path('Modules'));

        if (! is_dir($basePath)) {
            return [];
        }

        $modules     = [];
        $directories = File::directories($basePath);

        foreach ($directories as $directory) {
            $moduleName = basename($directory);

            // Check if it's a valid module (has ServiceProvider)
            if ($this->hasServiceProvider($directory, $moduleName)) {
                $modules[] = [
                    'name'     => $moduleName,
                    'path'     => $directory,
                    'provider' => $this->getServiceProviderClass($moduleName),
                    'routes'   => $this->getRoutesInfo($directory),
                ];
            }
        }

        // Sort modules by name
        usort($modules, fn ($a, $b) => strcmp($a['name'], $b['name']));

        return $modules;
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
        $providerPath = $this->getProviderPath($modulePath, $moduleName);
        return File::exists($providerPath);
    }

    /**
     * Check if a module has route files and return details.
     *
     * @param string $modulePath The module directory path
     * @return array{web: bool, api: bool, console: bool}
     */
    protected function getRoutesInfo(string $modulePath): array
    {
        $routesPath = $modulePath.'/'.config('easymodules.paths.routes', 'routes');

        return [
            'web'     => File::exists($routesPath.'/web.php'),
            'api'     => File::exists($routesPath.'/api.php'),
            'console' => File::exists($routesPath.'/console.php'),
        ];
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // DISPLAY METHODS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Display the command header with configuration information.
     *
     * @return void
     */
    protected function displayHeader(): void
    {
        $autoDiscover  = config('easymodules.auto_discover', true);
        $basePath      = config('easymodules.base_path', app_path('Modules'));
        $baseNamespace = config('easymodules.base_namespace', 'App\\Modules');

        // Get relative path from project root
        $relativeBasePath = str_replace(base_path(), '', $basePath);

        $this->info('📋 Easy Modules - Module Discovery');
        $this->newLine();

        $this->line("📁 Base Path: <comment>{$relativeBasePath}</comment>");
        $this->line("📦 Base Namespace: <comment>{$baseNamespace}</comment>");
        $this->line("🔍 Auto-Discovery: ".($autoDiscover ? '<info>✅ Enabled</info>' : '<error>❌ Disabled</error>'));

        if (! $autoDiscover) {
            $this->warn('⚠️  Auto-discovery is disabled. Modules need to be registered manually.');
        }

        $this->newLine();
    }

    /**
     * Display the modules table.
     *
     * @param array $modules The discovered modules
     * @return void
     */
    protected function displayModulesTable(array $modules): void
    {
        $headers = ['Module', 'Path', 'Provider'];

        if ($this->option('routes')) {
            $headers[] = 'Web';
            $headers[] = 'API';
            $headers[] = 'Console';
        }

        $rows = [];
        foreach ($modules as $module) {
            $relativePath = str_replace(base_path(), '', $module['path']);

            $row = [
                $module['name'],
                $relativePath,
                class_basename($module['provider']),
            ];

            if ($this->option('routes')) {
                $row[] = $module['routes']['web'] ? '✅' : '❌';
                $row[] = $module['routes']['api'] ? '✅' : '❌';
                $row[] = $module['routes']['console'] ? '✅' : '❌';
            }

            $rows[] = $row;
        }

        $this->table($headers, $rows);
    }

    /**
     * Display summary information.
     *
     * @param array $modules The discovered modules
     * @return void
     */
    protected function displaySummary(array $modules): void
    {
        $this->newLine();

        $totalModules = count($modules);

        $this->info("📊 Summary:");
        $this->line("   Total modules: <comment>{$totalModules}</comment>");

        if ($this->option('routes')) {
            $webRoutes     = count(array_filter($modules, fn ($module) => $module['routes']['web']));
            $apiRoutes     = count(array_filter($modules, fn ($module) => $module['routes']['api']));
            $consoleRoutes = count(array_filter($modules, fn ($module) => $module['routes']['console']));

            $this->line("   With web routes: <comment>{$webRoutes}</comment>");
            $this->line("   With API routes: <comment>{$apiRoutes}</comment>");
            $this->line("   With console routes: <comment>{$consoleRoutes}</comment>");
        }

        if (! config('easymodules.auto_discover', true)) {
            $this->newLine();
            $this->warn('💡 Tip: Enable auto_discover in config/easymodules.php for automatic module registration.');
        }
    }

    /**
     * Display message when no modules are found.
     *
     * @return void
     */
    protected function displayNoModulesFound(): void
    {
        $basePath = config('easymodules.base_path', app_path('Modules'));

        $this->warn('No modules found.');
        $this->newLine();
        $this->line('To create your first module, run:');
        $this->line('<comment>php artisan easymodules:new MyModule</comment>');
        $this->newLine();
        $this->line("Modules are expected in: <comment>{$basePath}</comment>");
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // UTILITY METHODS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Get the ServiceProvider file path for a module.
     *
     * @param string $modulePath The module directory path
     * @param string $moduleName The module name
     * @return string The ServiceProvider file path
     */
    protected function getProviderPath(string $modulePath, string $moduleName): string
    {
        $providerDir = config('easymodules.paths.provider', 'Providers');
        return $modulePath.'/'.$providerDir.'/'.$moduleName.'ServiceProvider.php';
    }

    /**
     * Get the fully qualified ServiceProvider class name.
     *
     * @param string $moduleName The module name
     * @return string The ServiceProvider class name
     */
    protected function getServiceProviderClass(string $moduleName): string
    {
        $baseNamespace     = config('easymodules.base_namespace', 'App\\Modules');
        $providerNamespace = config('easymodules.paths.provider', 'Providers');

        return $baseNamespace.'\\'.$moduleName.'\\'.str_replace('/', '\\', $providerNamespace).'\\'.$moduleName.'ServiceProvider';
    }
}
