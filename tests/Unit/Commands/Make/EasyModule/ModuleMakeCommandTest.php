<?php

declare(strict_types=1);

namespace Kadevland\EasyModules\Tests\Unit\Commands\Make\EasyModule;

use Illuminate\Filesystem\Filesystem;
use Kadevland\EasyModules\Tests\Support\TestCase;
use Kadevland\EasyModules\Commands\Make\EasyModule\ModuleMakeCommand;
use PHPUnit\Framework\Attributes\Test;

/**
 * Test suite for ModuleMakeCommand
 *
 * This command generates complete modules with Clean Architecture structure,
 * supporting scaffold generation, folder creation, and configuration validation.
 */
class ModuleMakeCommandTest extends TestCase
{
    protected ModuleMakeCommand $command;
    protected Filesystem $files;

    protected function setUp(): void
    {
        parent::setUp();

        $this->files = $this->app['files'];

        $this->command = new ModuleMakeCommand($this->files);
        $this->command->setLaravel($this->app);

        $output = new \Symfony\Component\Console\Output\BufferedOutput();
        $input = new \Symfony\Component\Console\Input\ArrayInput([]);
        $style = new \Illuminate\Console\OutputStyle($input, $output);
        $this->command->setOutput($style);

        // Set app environment to testing to disable prompts
        $this->app['config']->set('app.env', 'testing');
    }

    protected function tearDown(): void
    {
        // Enhanced cleanup - remove all test modules
        $testModules = [
            'MinimalModule',
            'CustomModule',
            'ScaffoldModule',
            'Alpha',
            'Beta',
            'AliasTest1',
            'AliasTest2',
            'CompleteModule',
            'TestModule',
            'FailModule',
            'FailModule2',
        ];

        foreach ($testModules as $module) {
            $modulePath = $this->testBasePath($module);
            if ($this->files->isDirectory($modulePath)) {
                $this->files->deleteDirectory($modulePath, true);
            }
        }

        parent::tearDown();
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // NIVEAU 5: UNIT TESTS - Individual method testing (fastest)
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Test module name validation
     */
    #[Test]
    public function it_validates_module_names(): void
    {
        $reflection = new \ReflectionClass($this->command);
        $method = $reflection->getMethod('isValidModuleName');
        $method->setAccessible(true);

        // Valid names
        $validNames = ['Blog', 'UserProfile', 'A', 'Post123', 'APIController'];
        foreach ($validNames as $name) {
            $this->assertTrue($method->invoke($this->command, $name), "Failed to validate: {$name}");
        }

        // Invalid names
        $invalidNames = ['123Blog', 'User-Profile', 'User Space', '@Invalid', 'user_profile'];
        foreach ($invalidNames as $name) {
            $this->assertFalse($method->invoke($this->command, $name), "Should invalidate: {$name}");
        }
    }

    /**
     * Test module path building
     */
    #[Test]
    public function it_builds_module_paths(): void
    {
        $basePath = '/test/modules';
        $this->app['config']->set('easymodules.base_path', $basePath);

        $reflection = new \ReflectionClass($this->command);
        $method = $reflection->getMethod('getModulePath');
        $method->setAccessible(true);

        $result = $method->invoke($this->command, 'TestModule');
        $expected = $basePath . DIRECTORY_SEPARATOR . 'TestModule';

        $this->assertEquals($expected, $result);
    }

    /**
     * Test invalid base path handling
     */
    #[Test]
    public function it_handles_invalid_base_paths(): void
    {
        $invalidPaths = ['', null, '   '];

        $reflection = new \ReflectionClass($this->command);
        $method = $reflection->getMethod('getModulePath');
        $method->setAccessible(true);

        foreach ($invalidPaths as $invalidPath) {
            $this->app['config']->set('easymodules.base_path', $invalidPath);
            $result = $method->invoke($this->command, 'TestModule');
            $this->assertNull($result, "Should return null for invalid path: " . var_export($invalidPath, true));
        }
    }

    /**
     * Test folders configuration merging
     */
    #[Test]
    public function it_merges_folders_configuration(): void
    {
        $foldersToGenerate = ['Domain/Entities', 'Application/Services'];
        $scaffoldFolders = ['Providers', 'config'];

        $this->app['config']->set('easymodules.folders_to_generate', $foldersToGenerate);
        $this->app['config']->set('easymodules.scaffold', $scaffoldFolders);

        $reflection = new \ReflectionClass($this->command);
        $method = $reflection->getMethod('getFoldersToCreate');
        $method->setAccessible(true);

        $result = $method->invoke($this->command);
        $expected = array_merge($foldersToGenerate, $scaffoldFolders);

        $this->assertEquals($expected, $result);
    }

    /**
     * Test configuration validation
     */
    #[Test]
    public function it_validates_configuration(): void
    {
        $reflection = new \ReflectionClass($this->command);
        $method = $reflection->getMethod('validateConfiguration');
        $method->setAccessible(true);

        // Valid configuration
        $this->app['config']->set('easymodules.base_path', $this->modulePath);
        $this->app['config']->set('easymodules.base_namespace', $this->moduleNamespace);
        $this->assertTrue($method->invoke($this->command));

        // Missing base_path
        $this->app['config']->set('easymodules.base_path', '');
        $this->assertFalse($method->invoke($this->command));

        // Missing base_namespace
        $this->app['config']->set('easymodules.base_path', $this->modulePath);
        $this->app['config']->set('easymodules.base_namespace', '');
        $this->assertFalse($method->invoke($this->command));
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // NIVEAU 4: BASIC MODULE CREATION TESTS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Test minimal module structure creation
     */
    #[Test]
    public function it_creates_minimal_module_structure(): void
    {
        $this->setupMinimalConfiguration();
        $this->createTestScaffoldStubs();

        // Test with minimal structure for performance
        $this->app['config']->set('easymodules.folders_to_generate', [
            'Domain/Entities', // Single folder to test generation
        ]);

        $this->artisan('easymodules:new', ['name' => ['MinimalModule']])
            ->assertExitCode(0);

        // Verify basic structure
        $this->assertModuleDirectoryExists('MinimalModule');
        $this->assertModuleDirectoryExists('MinimalModule/Domain/Entities');

        // Verify required scaffold
        $this->assertModuleDirectoryExists('MinimalModule/Providers');
        $this->assertModuleDirectoryExists('MinimalModule/config');
        $this->assertModuleDirectoryExists('MinimalModule/routes');
    }

    /**
     * Test custom folder structure creation
     */
    #[Test]
    public function it_creates_custom_folder_structure(): void
    {
        $this->setupMinimalConfiguration();
        $this->createTestScaffoldStubs();

        $customFolders = [
            'CustomDomain/ValueObjects',
            'CustomApp/UseCases',
        ];

        $this->app['config']->set('easymodules.folders_to_generate', $customFolders);

        $this->artisan('easymodules:new', ['name' => ['CustomModule']])
            ->assertExitCode(0);

        foreach ($customFolders as $folder) {
            $this->assertModuleDirectoryExists('CustomModule/' . $folder);
        }
    }

    /**
     * Test scaffold files creation
     */
    #[Test]
    public function it_creates_scaffold_files(): void
    {
        $this->setupMinimalConfiguration();
        $this->createTestScaffoldStubs();

        $this->app['config']->set('easymodules.folders_to_generate', []); // No custom folders

        $this->artisan('easymodules:new', ['name' => ['ScaffoldModule']])
            ->assertExitCode(0);

        // Verify scaffold files
        $this->assertFilenameExists('ScaffoldModule/config/config.php');
        $this->assertFilenameExists('ScaffoldModule/Providers/ScaffoldModuleServiceProvider.php');
        $this->assertFilenameExists('ScaffoldModule/routes/web.php');
        $this->assertFilenameExists('ScaffoldModule/routes/api.php');
        $this->assertFilenameExists('ScaffoldModule/routes/console.php');

        // Verify content with replacements
        $this->assertFileContains(['ScaffoldModule'], 'ScaffoldModule/config/config.php');
        $this->assertFileContains(['ScaffoldModuleServiceProvider'], 'ScaffoldModule/Providers/ScaffoldModuleServiceProvider.php');
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // NIVEAU 3: MULTIPLE MODULE & ALIAS TESTS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Test multiple modules creation
     */
    #[Test]
    public function it_handles_multiple_modules(): void
    {
        $this->setupMinimalConfiguration();
        $this->createTestScaffoldStubs();

        $moduleNames = ['Alpha', 'Beta'];

        $this->app['config']->set('easymodules.folders_to_generate', ['Domain']); // Minimal structure

        $this->artisan('easymodules:new', ['name' => $moduleNames])
            ->assertExitCode(0);

        foreach ($moduleNames as $moduleName) {
            $this->assertModuleDirectoryExists($moduleName);
            $this->assertModuleDirectoryExists($moduleName . '/Domain');
            $this->assertModuleDirectoryExists($moduleName . '/Providers');
        }
    }

    /**
     * Test command aliases functionality
     */
    #[Test]
    public function it_works_with_command_aliases(): void
    {
        $this->setupMinimalConfiguration();
        $this->createTestScaffoldStubs();

        $aliases = ['emodules:new', 'emodule:new'];
        $this->app['config']->set('easymodules.folders_to_generate', ['Domain']); // Minimal

        foreach ($aliases as $index => $alias) {
            $moduleName = 'AliasTest' . ($index + 1); // AliasTest1, AliasTest2

            $this->artisan($alias, ['name' => [$moduleName]])
                ->assertExitCode(0);

            $this->assertModuleDirectoryExists($moduleName);
        }
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // NIVEAU 2: ERROR HANDLING & VALIDATION TESTS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Test invalid module names rejection
     */
    #[Test]
    public function it_rejects_invalid_module_names(): void
    {
        $this->setupMinimalConfiguration();

        $invalidNames = ['123Invalid', 'Invalid-Name', 'Invalid Space', 'invalid@name'];

        foreach ($invalidNames as $invalidName) {
            $this->artisan('easymodules:new', ['name' => [$invalidName]])
                ->assertExitCode(1);

            $this->assertFalse(
                $this->files->isDirectory($this->testBasePath($invalidName)),
                "Invalid module '{$invalidName}' should not be created"
            );
        }
    }

    /**
     * Test graceful failure with invalid configuration
     */
    #[Test]
    public function it_fails_gracefully_with_invalid_configuration(): void
    {
        // Test without base_path
        $this->app['config']->set('easymodules.base_path', '');

        $this->artisan('easymodules:new', ['name' => ['FailModule']])
            ->assertExitCode(1);

        // Test without base_namespace
        $this->app['config']->set('easymodules.base_path', $this->modulePath);
        $this->app['config']->set('easymodules.base_namespace', '');

        $this->artisan('easymodules:new', ['name' => ['FailModule2']])
            ->assertExitCode(1);
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // NIVEAU 1: COMPREHENSIVE & INTEGRATION TESTS (slowest)
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Test complete module with full Clean Architecture structure
     */
    #[Test]
    public function it_creates_complete_module_with_full_structure(): void
    {
        $this->setupCompleteConfiguration();
        $this->createTestScaffoldStubs();

        $this->artisan('easymodules:new', ['name' => ['CompleteModule']])
            ->assertExitCode(0);

        // Verify complete Clean Architecture structure
        $expectedFolders = [
            'Domain/Entities',
            'Domain/Services',
            'Application/Services',
            'Application/DTOs',
            'Infrastructure/Models',
            'Infrastructure/Persistences',
            'Presentation/Http/Controllers',
            'Presentation/Http/Requests',
            'Database/Migrations',
            'Database/Factories',
            'Tests/Unit',
            'Tests/Feature',
        ];

        foreach ($expectedFolders as $folder) {
            $this->assertModuleDirectoryExists('CompleteModule/' . $folder);
        }

        // Verify scaffold
        $this->assertModuleDirectoryExists('CompleteModule/Providers');
        $this->assertModuleDirectoryExists('CompleteModule/config');
        $this->assertModuleDirectoryExists('CompleteModule/routes');

        // Verify scaffold files
        $this->assertFilenameExists('CompleteModule/config/config.php');
        $this->assertFilenameExists('CompleteModule/Providers/CompleteModuleServiceProvider.php');
        $this->assertFilenameExists('CompleteModule/routes/web.php');
        $this->assertFilenameExists('CompleteModule/routes/api.php');
        $this->assertFilenameExists('CompleteModule/routes/console.php');
    }

    /**
     * Test module creation prevents path duplication
     */
    #[Test]
    public function it_prevents_module_path_duplication(): void
    {
        $this->setupMinimalConfiguration();
        $this->createTestScaffoldStubs();

        $this->app['config']->set('easymodules.folders_to_generate', [
            'Domain/Entities',
            'Infrastructure/Models',
        ]);

        $this->artisan('easymodules:new', ['name' => ['DuplicationTest']])
            ->assertExitCode(0);

        // Verify correct paths
        $this->assertModuleDirectoryExists('DuplicationTest/Domain/Entities');
        $this->assertModuleDirectoryExists('DuplicationTest/Infrastructure/Models');

        // Verify NO duplicated paths
        $this->assertFalse(
            $this->files->isDirectory($this->testBasePath('DuplicationTest/Domain/Entities/Domain/Entities')),
            'Should not create duplicated path structure'
        );

        $this->assertFalse(
            $this->files->isDirectory($this->testBasePath('DuplicationTest/Infrastructure/Models/Infrastructure/Models')),
            'Should not create duplicated path structure'
        );
    }

    /**
     * Test module with custom configuration structures
     */
    #[Test]
    public function it_handles_custom_configuration_structures(): void
    {
        // Custom base configuration
        $this->app['config']->set('easymodules.base_path', $this->modulePath);
        $this->app['config']->set('easymodules.base_namespace', 'Custom\\Modules');
        $this->app['config']->set('easymodules.auto_discover', false);
        $this->app['config']->set('easymodules.scaffold', ['Providers', 'config']);

        // Custom folder structure
        $this->app['config']->set('easymodules.folders_to_generate', [
            'Core/Domain',
            'Core/Application',
            'Adapters/Http',
        ]);

        $this->createTestScaffoldStubs();

        $this->artisan('easymodules:new', ['name' => ['CustomConfigModule']])
            ->assertExitCode(0);

        // Verify custom structure
        $this->assertModuleDirectoryExists('CustomConfigModule/Core/Domain');
        $this->assertModuleDirectoryExists('CustomConfigModule/Core/Application');
        $this->assertModuleDirectoryExists('CustomConfigModule/Adapters/Http');
        $this->assertModuleDirectoryExists('CustomConfigModule/Providers');
        $this->assertModuleDirectoryExists('CustomConfigModule/config');
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // CONFIGURATION HELPERS - Reusable configurations
    // ═══════════════════════════════════════════════════════════════════════════════════════

    protected function setupMinimalConfiguration(): void
    {
        $this->app['config']->set('easymodules.base_path', $this->modulePath);
        $this->app['config']->set('easymodules.base_namespace', $this->moduleNamespace);
        $this->app['config']->set('easymodules.auto_discover', false);
        $this->app['config']->set('easymodules.scaffold', ['Providers', 'config', 'routes']);
        $this->app['config']->set('easymodules.paths', [
            'config' => 'config',
            'provider' => 'Providers',
            'routes' => 'routes',
        ]);
        $this->app['config']->set('easymodules.stubs_scaffold', [
            'config' => 'easymodules/scaffold/config.stub',
            'service_provider' => 'easymodules/scaffold/service_provider.stub',
            'route_web' => 'easymodules/scaffold/route_web.stub',
            'route_api' => 'easymodules/scaffold/route_api.stub',
            'route_console' => 'easymodules/scaffold/route_console.stub',
        ]);
    }

    protected function setupCompleteConfiguration(): void
    {
        $this->setupMinimalConfiguration();

        // Complete Clean Architecture structure
        $this->app['config']->set('easymodules.folders_to_generate', [
            // Domain Layer
            'Domain/Entities',
            'Domain/Services',
            'Domain/ValueObjects',

            // Application Layer
            'Application/Services',
            'Application/DTOs',
            'Application/Actions',

            // Infrastructure Layer
            'Infrastructure/Models',
            'Infrastructure/Persistences',
            'Infrastructure/Services',

            // Presentation Layer
            'Presentation/Http/Controllers',
            'Presentation/Http/Requests',
            'Presentation/Http/Resources',

            // Database Layer
            'Database/Migrations',
            'Database/Factories',
            'Database/Seeders',

            // Testing
            'Tests/Unit',
            'Tests/Feature',
        ]);
    }

    protected function createTestScaffoldStubs(): void
    {
        $this->createTestStub('easymodules/scaffold/config.stub', '<?php return ["name" => "{{ name }}"];');
        $this->createTestStub('easymodules/scaffold/service_provider.stub', '<?php class {{ class }} {}');
        $this->createTestStub('easymodules/scaffold/route_web.stub', '<?php // Web routes for {{ name }}');
        $this->createTestStub('easymodules/scaffold/route_api.stub', '<?php // API routes for {{ name }}');
        $this->createTestStub('easymodules/scaffold/route_console.stub', '<?php // Console routes for {{ name }}');
    }
}
