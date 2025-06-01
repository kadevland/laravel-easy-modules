<?php

declare(strict_types=1);

namespace Kadevland\EasyModules\Commands\Make\EasyModule;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

/**
 * Command to generate any stub type within a module
 *
 * This flexible command can generate any component type based on the
 * configuration defined in easymodules.php. It dynamically resolves
 * paths, namespaces, and stub files for maximum flexibility.
 *
 * @package Kadevland\EasyModules\Commands\Make\EasyModule
 * @author  Kadevland <kadevland@kaosland.net>
 * @version 1.0.0
 */
class StubMakeCommand extends ModuleGeneratorCommand
{
    // ═══════════════════════════════════════════════════════════════════════════════════════
    // PROPERTIES
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * The console command name
     *
     * @var string
     */
    protected $name = 'easymodules:make-stub';

    /**
     * The console command description
     *
     * @var string
     */
    protected $description = 'Generate any component type within a module using configured stubs';

    /**
     * The type of class being generated (dynamically set)
     *
     * @var string
     */
    protected $type = 'Component';

    /**
     * Available stub types from configuration
     *
     * @var array
     */
    protected array $availableStubs = [];

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
        //$this->configureModuleAliases($this->getCommandBaseName());
        parent::configure();
    }

    /**
     * Get the console command arguments
     *
     * @return array
     */
    protected function getArguments(): array
    {
        return [
            ['module', InputArgument::REQUIRED, 'The name of the module'],
            ['name', InputArgument::REQUIRED, 'The name of the component'],
            ['stub', InputArgument::REQUIRED, 'The type of stub to generate'],
        ];
    }

    /**
     * Get the console command options
     *
     * @return array
     */
    protected function getOptions(): array
    {
        return [
            ['force', 'f', InputOption::VALUE_NONE, 'Create the component even if it already exists'],
            ['list', 'l', InputOption::VALUE_NONE, 'List all available stub types'],
        ];
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // INPUT METHODS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Get the stub input argument
     *
     * @return string The cleaned stub type
     */
    protected function getInputStub(): string
    {
        // Safety check: ensure input is available
        if (! $this->input || ! $this->input->hasArgument('stub')) {
            return '';
        }

        return trim($this->argument('stub'));
    }

    /**
     * Get the command base name for alias configuration
     *
     * @return string The base command name
     */
    protected function getCommandBaseName(): string
    {
        return 'make-stub';
    }

    /**
     * Get the component type for configuration lookup
     *
     * @return string The component type from input
     */
    protected function getComponentType(): string
    {
        return $this->getInputStub();
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // COMMAND EXECUTION
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Execute the console command
     *
     * @return int Command exit code (0 for success, 1 for failure)
     */
    public function handle(): int
    {
        $this->loadAvailableStubs();

        if (! $this->isValidStubType($this->getInputStub())) {
            $this->showAvailableStubs();
            return self::FAILURE;
        }

        return parent::handle();
    }

    /**
     * Interact with the user before validating arguments
     *
     * @param mixed $input Input interface
     * @param mixed $output Output interface
     * @return void
     */
    protected function interact($input, $output): void
    {
        if ($this->option('list')) {
            $this->showAvailableStubs();
            exit(0);
        }

        parent::interact($input, $output);
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // STUB VALIDATION METHODS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Load available stubs from configuration
     *
     * @return void
     */
    protected function loadAvailableStubs(): void
    {
        $config = $this->laravel['config']->get('easymodules', []);

        $this->availableStubs = array_merge(
            array_keys(Arr::get($config, 'paths', [])),
            array_keys(Arr::get($config, 'stubs', []))
        );

        $this->availableStubs = array_unique($this->availableStubs);
        sort($this->availableStubs);
    }

    /**
     * Check if the provided stub type is valid
     *
     * @param string $stubType The stub type to validate
     * @return bool True if valid, false otherwise
     */
    protected function isValidStubType(string $stubType): bool
    {
        return in_array($stubType, $this->availableStubs);
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // STUB REPLACEMENT METHODS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Build replacement variables for the stub
     *
     * @return array Array of replacements
     */
    protected function buildReplacements(): array
    {
        $baseReplacements = parent::buildReplacements();

        // Get the component path and convert to namespace using input directly
        $stubType = $this->getInputStub();

        // If no stub type available, return base replacements only
        if (empty($stubType)) {
            return $baseReplacements;
        }

        $componentPath = $this->laravel['config']->get("easymodules.paths.{$stubType}");
        $namespace     = $componentPath
            ? $this->moduleNamespace($stubType, $componentPath)
            : $this->rootModuleNamespace();

        return array_merge($baseReplacements, [
            '{{ namespace }}' => $namespace,
            '{{ class }}'     => Str::studly($this->getNameInput()),
            '{{ stub }}'      => Str::studly($stubType),
        ]);
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // USER INTERFACE METHODS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Show available stub types to the user
     *
     * @return void
     */
    protected function showAvailableStubs(): void
    {
        $this->info('📋 Available Stub Types:');
        $this->newLine();

        if (empty($this->availableStubs)) {
            $this->warn('No stub types configured in easymodules.php');
            return;
        }

        // Simple list without forced grouping
        foreach ($this->availableStubs as $stub) {
            $this->line("   • {$stub}");
        }

        $this->newLine();
        $this->info('Usage Examples:');
        $this->line('  php artisan easymodules:make-stub Blog UserDto dto');
        $this->line('  php artisan easymodules:make-stub Blog UserDtoMapper dto_mapper');
    }
}
