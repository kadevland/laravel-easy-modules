<?php

declare(strict_types=1);

namespace Kadevland\EasyModules\Tests\Unit\Commands\Console;

use Kadevland\EasyModules\Tests\Support\TestCase;
use Kadevland\EasyModules\Commands\Console\ListModulesCommand;
use PHPUnit\Framework\Attributes\Test;

/**
 * Test suite for ListModulesCommand
 *
 * This command discovers and lists all modules in the project,
 * providing information about module structure, routes, and providers
 * with support for both human-readable and JSON output formats.
 */
class ListModulesCommandTest extends TestCase
{
    protected ListModulesCommand $command;
    protected \Symfony\Component\Console\Output\BufferedOutput $output;

    protected function setUp(): void
    {
        parent::setUp();

        // Set app environment to testing to disable prompts
        $this->app['config']->set('app.env', 'testing');

        // IMPORTANT: Keep original command setup for unit testing
        $this->command = new ListModulesCommand();
        $this->command->setLaravel($this->app);

        // Setup console output for direct command testing
        $this->output = new \Symfony\Component\Console\Output\BufferedOutput();
        $input = new \Symfony\Component\Console\Input\ArrayInput([]);
        $style = new \Illuminate\Console\OutputStyle($input, $this->output);
        $this->command->setOutput($style);

        $this->setupTestConfiguration();
    }

    protected function tearDown(): void
    {
        // Enhanced cleanup - remove all test modules
        $testModules = [
            'ValidModule',
            'InvalidModule',
            'TestModule',
            'BlogModule',
            'UserModule',
            'AModule',
            'ZModule',
            'MModule',
            'CustomModule',
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
    // NIVEAU 5: BASIC MODULE DISCOVERY TESTS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Test basic module discovery when no modules exist
     */
    #[Test]
    public function it_discovers_no_modules_when_directory_empty(): void
    {
        $reflection = new \ReflectionClass($this->command);
        $method = $reflection->getMethod('discoverModules');
        $method->setAccessible(true);

        $result = $method->invoke($this->command);

        $this->assertEmpty($result);
    }

    /**
     * Test module discovery with valid service provider
     */
    #[Test]
    public function it_discovers_modules_with_service_provider(): void
    {
        $this->createModuleStructure('ValidModule');

        $reflection = new \ReflectionClass($this->command);
        $method = $reflection->getMethod('discoverModules');
        $method->setAccessible(true);

        $result = $method->invoke($this->command);

        $this->assertCount(1, $result);
        $this->assertEquals('ValidModule', $result[0]['name']);
        $this->assertStringContainsString('ValidModule', $result[0]['path']);
        $this->assertStringContainsString('ValidModuleServiceProvider', $result[0]['provider']);
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // NIVEAU 4: DISCOVERY CORRECTNESS TESTS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Test module discovery ignores invalid directories
     */
    #[Test]
    public function it_ignores_directories_without_service_provider(): void
    {
        // Create directory without ServiceProvider
        $modulePath = $this->testBasePath('InvalidModule');
        $this->files->makeDirectory($modulePath, 0755, true);
        // No ServiceProvider file created

        $reflection = new \ReflectionClass($this->command);
        $method = $reflection->getMethod('discoverModules');
        $method->setAccessible(true);

        $result = $method->invoke($this->command);

        $this->assertEmpty($result);
    }

    /**
     * Test route detection functionality
     */
    #[Test]
    public function it_detects_route_files_correctly(): void
    {
        $this->createModuleStructure('TestModule', [
            'routes' => ['web', 'api', 'console'],
        ]);

        $reflection = new \ReflectionClass($this->command);
        $method = $reflection->getMethod('getRoutesInfo');
        $method->setAccessible(true);

        $result = $method->invoke($this->command, $this->testBasePath('TestModule'));

        $this->assertTrue($result['web']);
        $this->assertTrue($result['api']);
        $this->assertTrue($result['console']);
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // NIVEAU 3: SORTING AND VALIDATION TESTS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Test modules sorting and service provider path building
     *
     * This test ensures that module discovery works properly, results
     * are sorted alphabetically, and service provider paths are built correctly.
     */
    #[Test]
    public function it_sorts_modules_and_builds_correct_paths(): void
    {
        $this->createModuleStructure('ZModule');
        $this->createModuleStructure('AModule');
        $this->createModuleStructure('MModule');

        // Test sorting
        $reflection = new \ReflectionClass($this->command);
        $method = $reflection->getMethod('discoverModules');
        $method->setAccessible(true);

        $result = $method->invoke($this->command);

        $this->assertCount(3, $result);
        $this->assertEquals('AModule', $result[0]['name']);
        $this->assertEquals('MModule', $result[1]['name']);
        $this->assertEquals('ZModule', $result[2]['name']);

        // Test service provider class name building
        $providerMethod = $reflection->getMethod('getServiceProviderClass');
        $providerMethod->setAccessible(true);

        $providerClass = $providerMethod->invoke($this->command, 'TestModule');
        $expected = 'App\\Modules\\TestModule\\Providers\\TestModuleServiceProvider';

        $this->assertEquals($expected, $providerClass);
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // NIVEAU 2: CUSTOM CONFIGURATION TESTS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Test JSON output functionality
     */
    #[Test]
    public function it_outputs_modules_as_json(): void
    {
        $this->createModuleStructure('BlogModule');
        $this->createModuleStructure('UserModule');

        $output = $this->executeCommandWithJson(['json' => true]);
        $data = json_decode($output, true);

        $this->assertIsArray($data);
        $this->assertCount(2, $data);

        // Check first module (alphabetically sorted)
        $this->assertEquals('BlogModule', $data[0]['name']);
        $this->assertStringContainsString('BlogModule', $data[0]['path']);
        $this->assertStringContainsString('BlogModuleServiceProvider', $data[0]['provider']);

        // Check second module
        $this->assertEquals('UserModule', $data[1]['name']);
        $this->assertStringContainsString('UserModule', $data[1]['path']);
        $this->assertStringContainsString('UserModuleServiceProvider', $data[1]['provider']);
    }

    /**
     * Test custom provider paths configuration
     */
    #[Test]
    public function it_handles_custom_provider_paths(): void
    {
        $this->app['config']->set('easymodules.paths.provider', 'Custom/Providers');

        $this->createModuleStructure('CustomModule', [
            'provider_path' => 'Custom/Providers',
        ]);

        $output = $this->executeCommandWithJson(['json' => true]);
        $data = json_decode($output, true);

        $this->assertCount(1, $data);
        $this->assertEquals('CustomModule', $data[0]['name']);
        $this->assertStringContainsString('CustomModuleServiceProvider', $data[0]['provider']);
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // NIVEAU 1: EDGE CASE & INTEGRATION TESTS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Test command with artisan integration
     */
    #[Test]
    public function it_displays_no_modules_found_message(): void
    {
        $this->artisan('easymodules:list')
            ->expectsOutputToContain('Easy Modules - Module Discovery')
            ->expectsOutputToContain('No modules found.')
            ->assertExitCode(0);
    }

    /**
     * Test auto-discovery status display
     */
    #[Test]
    public function it_shows_auto_discovery_status(): void
    {
        $this->app['config']->set('easymodules.auto_discover', true);

        $this->artisan('easymodules:list')
            ->expectsOutputToContain('Auto-Discovery: ✅ Enabled')
            ->assertExitCode(0);

        $this->app['config']->set('easymodules.auto_discover', false);

        $this->artisan('easymodules:list')
            ->expectsOutputToContain('Auto-Discovery: ❌ Disabled')
            ->expectsOutputToContain('Auto-discovery is disabled. Modules need to be registered manually.')
            ->assertExitCode(0);
    }

    /**
     * Test command aliases work correctly
     */
    #[Test]
    public function it_works_with_command_aliases(): void
    {
        $aliases = ['emodules:list', 'emodule:list'];

        foreach ($aliases as $alias) {
            $this->artisan($alias)
                ->expectsOutputToContain('Easy Modules - Module Discovery')
                ->expectsOutputToContain('No modules found.')
                ->assertExitCode(0);
        }
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // HELPER METHODS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Helper method to execute command with JSON option and get output
     */
    protected function executeCommandWithJson(array $options = []): string
    {
        // Create a mock command with the options
        $mockCommand = $this->getMockBuilder(get_class($this->command))
            ->onlyMethods(['option'])
            ->getMock();

        $mockCommand->method('option')
            ->willReturnCallback(function ($option) use ($options) {
                return $options[$option] ?? false;
            });

        $mockCommand->setLaravel($this->app);

        // Setup output capture
        $output = new \Symfony\Component\Console\Output\BufferedOutput();
        $input = new \Symfony\Component\Console\Input\ArrayInput([]);
        $style = new \Illuminate\Console\OutputStyle($input, $output);
        $mockCommand->setOutput($style);

        // Execute and return output
        $mockCommand->handle();
        return $output->fetch();
    }

    /**
     * Create a module structure for testing
     */
    protected function createModuleStructure(string $moduleName, array $options = []): void
    {
        $modulePath = $this->testBasePath($moduleName);
        $this->files->makeDirectory($modulePath, 0755, true);

        // Create ServiceProvider (required for discovery)
        $providerPath = $options['provider_path'] ?? 'Providers';
        $fullProviderPath = $modulePath.'/'.$providerPath;
        $this->files->makeDirectory($fullProviderPath, 0755, true);
        $this->files->put(
            $fullProviderPath.'/'.$moduleName.'ServiceProvider.php',
            '<?php // Mock ServiceProvider for '.$moduleName
        );

        // Create route files if specified
        if (isset($options['routes'])) {
            $routesPath = $options['routes_path'] ?? 'routes';
            $fullRoutesPath = $modulePath.'/'.$routesPath;
            $this->files->makeDirectory($fullRoutesPath, 0755, true);

            foreach ($options['routes'] as $routeType) {
                $this->files->put(
                    $fullRoutesPath.'/'.$routeType.'.php',
                    '<?php // Mock '.$routeType.' routes for '.$moduleName
                );
            }
        }
    }

    /**
     * Setup test configuration
     */
    protected function setupTestConfiguration(): void
    {
        $this->app['config']->set('easymodules.base_path', $this->modulePath);
        $this->app['config']->set('easymodules.base_namespace', $this->moduleNamespace);
        $this->app['config']->set('easymodules.auto_discover', true);
        $this->app['config']->set('easymodules.paths', [
            'provider' => 'Providers',
            'routes' => 'routes',
        ]);
    }
}
