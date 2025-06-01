<?php

declare(strict_types=1);

namespace Kadevland\EasyModules\Tests\Support;

use Illuminate\Filesystem\Filesystem;
use Orchestra\Testbench\TestCase as Orchestra;
use Kadevland\EasyModules\Providers\EasyModulesServiceProvider;

abstract class TestCase extends Orchestra
{
    protected Filesystem $files;
    protected string     $modulePath      = '';
    protected string     $moduleNamespace = 'App\\Modules';

    protected function setUp(): void
    {
        parent::setUp();
        $this->modulePath = $this->app->basePath('app/Modules');
        $this->files      = $this->app['files'];
        $this->setupTestConfig();
    }

    protected function tearDown(): void
    {
        try {
            if ($this->files->isDirectory($this->modulePath)) {
                $this->files->deleteDirectory($this->modulePath, false);
            }
        } catch (\Exception $e) {
            echo "\nWarning: Could not clean up test directory: ".$e->getMessage()."\n";
        }
        parent::tearDown();
    }

    public static function tearDownAfterClass(): void
    {
        parent::tearDownAfterClass();
    }

    protected function getPackageProviders($app): array
    {
        return [EasyModulesServiceProvider::class];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);
    }

    protected function setupTestConfig(): void
    {
        $this->app['config']->set('easymodules.base_path', $this->modulePath);
        $this->app['config']->set('easymodules.base_namespace', $this->moduleNamespace);
        $this->app['config']->set('easymodules.auto_discover', false);
    }

    // ───────────────────────────────────────────────────────────────────────────
    // Enhanced Testing Methods for EasyModules
    // ───────────────────────────────────────────────────────────────────────────

    /**
     * Set the path configuration for a specific component type.
     *
     * This helper method configures the path where a component type should be
     * generated within modules, making tests cleaner and more maintainable.
     *
     * @param string $type The component type (cast, channel, controller, etc.)
     * @param string $path The path within the module structure
     * @return void
     */
    protected function setComponentPath(string $type, string $path): void
    {
        $this->app['config']->set("easymodules.paths.{$type}", $path);
    }

    /**
     * Set the stub configuration for a specific component type.
     *
     * This helper method configures the stub file that should be used
     * for generating a component type within modules, making tests cleaner
     * and more maintainable.
     *
     * @param string $type The component type (cast, channel, controller, etc.)
     * @param string $stubPath The stub file path relative to resources/stubs
     * @return void
     */
    protected function setComponentStub(string $type, string $stubPath): void
    {
        $this->app['config']->set("easymodules.stubs.{$type}", $stubPath);
    }

    /**
     * Execute an EasyModules command and assert success.
     *
     * This method provides a fluent interface for running EasyModules commands
     * and automatically asserts that the command executed successfully.
     *
     * @param string $command The command name without 'easymodules:' prefix (e.g., 'make-cast')
     * @param string $module The target module name
     * @param string $name The component name to generate
     * @param array $options Additional command options
     * @return $this For method chaining
     */
    protected function runEasyModulesCommand(string $command, string $module, string $name, array $options = []): self
    {
        $params = array_merge([
            'module' => $module,
            'name'   => $name,
        ], $options);

        $this->artisan("easymodules:{$command}", $params)
            ->assertExitCode(0);

        return $this;
    }

    /**
     * Generate basic class assertions for namespace and class declaration.
     *
     * Creates the fundamental assertions that verify correct namespace
     * and class name generation for any module component.
     *
     * @param string $module The module name
     * @param string $path The path within the module
     * @param string $className The expected class name
     * @return array Array of assertion strings
     */
    protected function getBasicClassAssertions(string $module, string $path, string $className): array
    {
        $namespace = $this->moduleNamespace.'\\'.$module.'\\'.str_replace('/', '\\', $path);

        return [
            "namespace {$namespace};",
            "class {$className}",
        ];
    }

    /**
     * Assert that a module component exists with expected content.
     *
     * This method combines file existence checking with content verification,
     * automatically including basic namespace and class assertions.
     *
     * @param string $module The module name
     * @param string $path The path within the module
     * @param string $className The expected class name
     * @param array $additionalContent Additional content to verify
     * @return void
     */
    protected function assertModuleComponentExists(string $module, string $path, string $className, array $additionalContent = []): void
    {
        $filePath = "{$module}/{$path}/{$className}.php";

        $expectedContent = array_merge(
            $this->getBasicClassAssertions($module, $path, $className),
            $additionalContent
        );

        $this->assertFileContains($expectedContent, $filePath);
    }

    /**
     * Assert that a file does not exist.
     *
     * @param string $file The file path relative to the test base path
     * @return void
     */
    protected function assertFilenameNotExists(string $file): void
    {
        $this->assertFalse(
            $this->files->exists($this->testBasePath($file)),
            "Assert file {$file} does not exist"
        );
    }

    /**
     * More readable alias for assertFileNotContains.
     *
     * @param array $notContains Array of strings that should not be in the file
     * @param string $file The file path to check
     * @param string $message Optional assertion message
     * @return void
     */
    protected function assertFileDoesNotContains(array $notContains, string $file, string $message = ''): void
    {
        $this->assertFileNotContains($notContains, $file, $message);
    }

    // ───────────────────────────────────────────────────────────────────────────
    // Existing Core Testing Methods (Unchanged)
    // ───────────────────────────────────────────────────────────────────────────

    protected function createTestStub(string $stubPath, string $content): void
    {
        $fullPath = resource_path("stubs/{$stubPath}");
        $this->files->ensureDirectoryExists(dirname($fullPath));
        $this->files->put($fullPath, $content);
    }

    protected function assertFilenameExists(string $file): void
    {
        $this->assertTrue(
            $this->files->exists($this->testBasePath($file)),
            "Assert file {$file} does exist"
        );
    }

    protected function assertModuleDirectoryExists(string $path): void
    {
        $this->assertTrue(
            $this->files->isDirectory($this->testBasePath($path)),
            "Module directory {$path} should exist : ".$this->testBasePath($path)
        );
    }

    protected function assertFileContains(array $contains, string $file, string $message = ''): void
    {
        $this->assertFilenameExists($file);

        $haystack = $this->files->get($this->testBasePath($file));

        foreach ($contains as $needle) {
            $this->assertStringContainsString($needle, $haystack, $message);
        }
    }

    protected function assertFileNotContains(array $notContains, string $file, string $message = ''): void
    {
        $this->assertFilenameExists($file);

        $haystack = $this->files->get($this->testBasePath($file));

        foreach ($notContains as $needle) {
            $this->assertStringNotContainsString($needle, $haystack, $message);
        }
    }

    protected function testBasePath(string $file = ''): string
    {
        if (empty($file)) {
            return $this->modulePath;
        }

        return $this->modulePath.'/'.ltrim($file, '/');
    }
}
