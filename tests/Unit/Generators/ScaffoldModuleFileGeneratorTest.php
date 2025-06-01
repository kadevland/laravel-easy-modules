<?php

declare(strict_types=1);

namespace Kadevland\EasyModules\Tests\Unit\Generators;

use Kadevland\EasyModules\Tests\Support\TestCase;
use Kadevland\EasyModules\Generators\ScaffoldModuleFileGenerator;

class ScaffoldModuleFileGeneratorTest extends TestCase
{
    protected $generator;
    protected $testSettings;
    protected $testModuleName;

    protected function setUp(): void
    {
        parent::setUp();

        $this->testModuleName = 'TestModule';
        $this->testSettings   = [
            'base_path'      => $this->modulePath, // Use TestCase property
            'base_namespace' => $this->moduleNamespace, // Use TestCase property
            'auto_discover'  => false, // Disable for testing
            'paths'          => [
                'config'   => 'config',
                'provider' => 'Providers',
                'routes'   => 'routes',
            ],
            'stubs_scaffold' => [
                'config'           => 'easymodules/scaffold/config.stub',
                'service_provider' => 'easymodules/scaffold/service_provider.stub',
                'route_web'        => 'easymodules/scaffold/route_web.stub',
                'route_api'        => 'easymodules/scaffold/route_api.stub',
                'route_console'    => 'easymodules/scaffold/route_console.stub',
            ],
        ];

        $this->generator = new ScaffoldModuleFileGenerator($this->testSettings, $this->testModuleName);
    }

    public function test_it_initializes_with_correct_base_path(): void
    {
        $reflection = new \ReflectionClass($this->generator);
        $property   = $reflection->getProperty('basePath');
        $property->setAccessible(true);

        $result   = $property->getValue($this->generator);
        $expected = $this->modulePath.DIRECTORY_SEPARATOR.$this->testModuleName;

        $this->assertEquals($expected, $result);
    }

    public function test_it_initializes_with_correct_base_namespace(): void
    {
        $reflection = new \ReflectionClass($this->generator);
        $property   = $reflection->getProperty('baseNamespace');
        $property->setAccessible(true);

        $result   = $property->getValue($this->generator);
        $expected = $this->moduleNamespace.'\\'.$this->testModuleName;

        $this->assertEquals($expected, $result);
    }

    public function test_it_builds_replacement_variables(): void
    {
        $reflection = new \ReflectionClass($this->generator);
        $method     = $reflection->getMethod('buildReplacementVariables');
        $method->setAccessible(true);

        $result = $method->invoke($this->generator);

        // Should contain basic replacements
        $this->assertArrayHasKey('{{ name }}', $result);
        $this->assertArrayHasKey('{{ class }}', $result);
        $this->assertArrayHasKey('{{ base_namespace }}', $result);

        // Check values
        $this->assertEquals($this->testModuleName, $result['{{ name }}']);
        $this->assertEquals($this->testModuleName.'ServiceProvider', $result['{{ class }}']);
        $this->assertEquals($this->moduleNamespace.'\\'.$this->testModuleName, $result['{{ base_namespace }}']);
    }

    public function test_it_adds_replacement_in_multiple_formats(): void
    {
        $reflection = new \ReflectionClass($this->generator);
        $method     = $reflection->getMethod('addReplacement');
        $method->setAccessible(true);

        $replaces = [];
        // FIX: Use invokeArgs() to handle pass-by-reference
        $method->invokeArgs($this->generator, ['test_key', 'TestValue', &$replaces]);

        // Should add multiple formats
        $this->assertArrayHasKey('{{ test_key }}', $replaces);
        $this->assertArrayHasKey('{{test_key}}', $replaces);
        $this->assertEquals('TestValue', $replaces['{{ test_key }}']);
        $this->assertEquals('TestValue', $replaces['{{test_key}}']);

        // ALTERNATIVE if issue persists:
        /*
        // Test via buildReplacementVariables() which uses addReplacement()
        $reflection = new \ReflectionClass($this->generator);
        $method     = $reflection->getMethod('buildReplacementVariables');
        $method->setAccessible(true);

        $result = $method->invoke($this->generator);

        // Verify that replacements are created in different formats
        $this->assertArrayHasKey('{{ name }}', $result);
        $this->assertArrayHasKey('{{name}}', $result);
        $this->assertArrayHasKey('{{ class }}', $result);
        $this->assertArrayHasKey('{{class}}', $result);

        // Verify that values are identical for different formats
        $this->assertEquals($result['{{ name }}'], $result['{{name}}']);
        $this->assertEquals($result['{{ class }}'], $result['{{class}}']);
        */
    }

    public function test_it_gets_target_file_path_for_config(): void
    {

        $configPath = 'config';
        $this->app['config']->set('easymodules.paths.config', $configPath);

        $reflection = new \ReflectionClass($this->generator);
        $method     = $reflection->getMethod('getTargetFilePath');
        $method->setAccessible(true);

        $result   = $method->invoke($this->generator, 'config');
        $expected = $this->testBasePath($this->testModuleName.'/'.$configPath.'/config.php');

        $this->assertEquals($expected, $result);
    }

    public function test_it_gets_target_file_path_for_service_provider(): void
    {
        $providerPath = 'Providers';
        $this->app['config']->set('easymodules.paths.provider', $providerPath);

        $reflection = new \ReflectionClass($this->generator);
        $method     = $reflection->getMethod('getTargetFilePath');
        $method->setAccessible(true);

        $result   = $method->invoke($this->generator, 'service_provider');
        $expected = $this->testBasePath($this->testModuleName.'/'.$providerPath.'/'.$this->testModuleName.'ServiceProvider.php');

        $this->assertEquals($expected, $result);
    }

    public function test_it_gets_target_file_path_for_routes(): void
    {

        $routesPath = 'routes';
        $this->app['config']->set('easymodules.paths.routes', $routesPath);

        $reflection = new \ReflectionClass($this->generator);
        $method     = $reflection->getMethod('getTargetFilePath');
        $method->setAccessible(true);

        $webRoute     = $method->invoke($this->generator, 'route_web');
        $apiRoute     = $method->invoke($this->generator, 'route_api');
        $consoleRoute = $method->invoke($this->generator, 'route_console');

        $this->assertEquals($this->testBasePath($this->testModuleName.'/'.$routesPath.'/web.php'), $webRoute);
        $this->assertEquals($this->testBasePath($this->testModuleName.'/'.$routesPath.'/api.php'), $apiRoute);
        $this->assertEquals($this->testBasePath($this->testModuleName.'/'.$routesPath.'/console.php'), $consoleRoute);
    }

    public function test_it_returns_null_for_unknown_scaffold_type(): void
    {
        $reflection = new \ReflectionClass($this->generator);
        $method     = $reflection->getMethod('getTargetFilePath');
        $method->setAccessible(true);

        $result = $method->invoke($this->generator, 'unknown_type');

        $this->assertNull($result);
    }

    public function test_it_resolves_stub_path(): void
    {
        // Create a temporary stub file for testing
        $stubContent = '<?php // Test stub content';
        $this->createTestStub('easymodules/scaffold/config.stub', $stubContent);

        $reflection = new \ReflectionClass($this->generator);
        $method     = $reflection->getMethod('resolveStubPath');
        $method->setAccessible(true);

        $result       = $method->invoke($this->generator, 'easymodules/scaffold/config.stub');
        $expectedPath = resource_path('stubs/easymodules/scaffold/config.stub');

        $this->assertEquals($expectedPath, $result);
        $this->assertTrue(file_exists($result));

        // Cleanup handled by TestCase tearDown
    }

    public function test_it_processes_stub_file_with_replacements(): void
    {
        // Create a temporary stub file
        $stubContent = 'Module: {{ name }}, Class: {{ class }}';
        $this->createTestStub('test.stub', $stubContent);

        $replacements = [
            '{{ name }}'  => 'TestModule',
            '{{ class }}' => 'TestClass',
        ];

        $reflection = new \ReflectionClass($this->generator);
        $method     = $reflection->getMethod('processStubFile');
        $method->setAccessible(true);

        $result = $method->invoke($this->generator, 'test.stub', $replacements);

        $this->assertEquals('Module: TestModule, Class: TestClass', $result);
    }

    public function test_it_ensures_directory_exists(): void
    {
        $testDir = $this->testBasePath('test_directory');

        // Ensure directory doesn't exist initially
        if (is_dir($testDir)) {
            rmdir($testDir);
        }

        $reflection = new \ReflectionClass($this->generator);
        $method     = $reflection->getMethod('ensureDirectoryExists');
        $method->setAccessible(true);

        $method->invoke($this->generator, $testDir);

        $this->assertModuleDirectoryExists('test_directory');

        // Cleanup handled by TestCase tearDown
    }

    public function test_it_gets_path_from_settings(): void
    {
        $reflection = new \ReflectionClass($this->generator);
        $method     = $reflection->getMethod('getPathFromSettings');
        $method->setAccessible(true);

        $result = $method->invoke($this->generator, 'config');
        $this->assertEquals('config', $result);

        $result = $method->invoke($this->generator, 'provider');
        $this->assertEquals('Providers', $result);

        $result = $method->invoke($this->generator, 'nonexistent');
        $this->assertNull($result);
    }

    public function test_it_gets_scaffold_stubs(): void
    {
        $reflection = new \ReflectionClass($this->generator);
        $method     = $reflection->getMethod('getScaffoldStubs');
        $method->setAccessible(true);

        $result = $method->invoke($this->generator);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('config', $result);
        $this->assertArrayHasKey('service_provider', $result);
        $this->assertEquals('easymodules/scaffold/config.stub', $result['config']);
    }

    public function test_it_gets_service_provider_class(): void
    {

        $providerPath = 'Providers';
        $this->app['config']->set('easymodules.paths.provider', $providerPath);

        $reflection = new \ReflectionClass($this->generator);
        $method     = $reflection->getMethod('getServiceProviderClass');
        $method->setAccessible(true);

        $result   = $method->invoke($this->generator);
        $expected = $this->moduleNamespace.'\\'.$this->testModuleName.'\\'.$providerPath.'\\'.$this->testModuleName.'ServiceProvider';

        $this->assertEquals($expected, $result);
    }

    public function test_it_checks_auto_discover_configuration(): void
    {
        // Test with auto_discover disabled
        $reflection = new \ReflectionClass($this->generator);
        $method     = $reflection->getMethod('shouldAutoDiscover');
        $method->setAccessible(true);

        $result = $method->invoke($this->generator);
        $this->assertFalse($result);

        // Test with auto_discover enabled but no service provider file
        $settingsWithAutoDiscover  = array_merge($this->testSettings, ['auto_discover' => true]);
        $generatorWithAutoDiscover = new ScaffoldModuleFileGenerator($settingsWithAutoDiscover, $this->testModuleName);

        $reflection = new \ReflectionClass($generatorWithAutoDiscover);
        $method     = $reflection->getMethod('shouldAutoDiscover');
        $method->setAccessible(true);

        $result = $method->invoke($generatorWithAutoDiscover);
        $this->assertFalse($result); // Still false because ServiceProvider file doesn't exist
    }

    public function test_it_logs_generated_and_failed_files(): void
    {
        $reflection = new \ReflectionClass($this->generator);

        // Test logging generated file
        $logGeneratedMethod = $reflection->getMethod('logGeneratedFile');
        $logGeneratedMethod->setAccessible(true);
        $logGeneratedMethod->invoke($this->generator, 'config', '/path/to/config.php');

        $getGeneratedMethod = $reflection->getMethod('getGeneratedFiles');
        $getGeneratedMethod->setAccessible(true);
        $generatedFiles = $getGeneratedMethod->invoke($this->generator);

        $this->assertCount(1, $generatedFiles);
        $this->assertEquals('config', $generatedFiles[0]['type']);
        $this->assertEquals('/path/to/config.php', $generatedFiles[0]['path']);

        // Test logging failed file
        $logFailedMethod = $reflection->getMethod('logFailedFile');
        $logFailedMethod->setAccessible(true);
        $logFailedMethod->invoke($this->generator, 'service_provider', 'File not found');

        $getFailedMethod = $reflection->getMethod('getFailedFiles');
        $getFailedMethod->setAccessible(true);
        $failedFiles = $getFailedMethod->invoke($this->generator);

        $this->assertCount(1, $failedFiles);
        $this->assertEquals('service_provider', $failedFiles[0]['type']);
        $this->assertEquals('File not found', $failedFiles[0]['reason']);

        // Test success check
        $wasSuccessfulMethod = $reflection->getMethod('wasSuccessful');
        $wasSuccessfulMethod->setAccessible(true);
        $result = $wasSuccessfulMethod->invoke($this->generator);

        $this->assertFalse($result); // Should be false because we have failed files
    }

    public function test_it_inherits_path_namespace_converter_functionality(): void
    {
        // Test that the class uses PathNamespaceConverter methods
        $this->assertTrue(method_exists($this->generator, 'pathToNamespace'));
        $this->assertTrue(method_exists($this->generator, 'generatePhpFilePath'));

        // Test an inherited method
        $reflection = new \ReflectionClass($this->generator);
        $method     = $reflection->getMethod('pathToNamespace');
        $method->setAccessible(true);

        $result = $method->invoke($this->generator, 'Providers/TestProvider');
        $this->assertEquals('Providers\\TestProvider', $result);
    }

    public function test_it_generates_all_scaffold_files(): void
    {
        // Setup test stubs
        $this->createTestStub('easymodules/scaffold/config.stub', '<?php return ["name" => "{{ name }}"];');
        $this->createTestStub('easymodules/scaffold/service_provider.stub', '<?php class {{ class }} {}');
        $this->createTestStub('easymodules/scaffold/route_web.stub', '<?php // Web routes for {{ name }}');
        $this->createTestStub('easymodules/scaffold/route_api.stub', '<?php // API routes for {{ name }}');
        $this->createTestStub('easymodules/scaffold/route_console.stub', '<?php // Console routes for {{ name }}');

        // Set explicit config
        $configPath   = 'config';
        $providerPath = 'Providers';
        $routesPath   = 'routes';

        $this->app['config']->set('easymodules.paths.config', $configPath);
        $this->app['config']->set('easymodules.paths.provider', $providerPath);
        $this->app['config']->set('easymodules.paths.routes', $routesPath);

        $result = $this->generator->generate();

        $this->assertTrue($result);

        // Verify files were created
        $this->assertFilenameExists($this->testModuleName.'/'.$configPath.'/config.php');
        $this->assertFilenameExists($this->testModuleName.'/'.$providerPath.'/'.$this->testModuleName.'ServiceProvider.php');
        $this->assertFilenameExists($this->testModuleName.'/'.$routesPath.'/web.php');
        $this->assertFilenameExists($this->testModuleName.'/'.$routesPath.'/api.php');
        $this->assertFilenameExists($this->testModuleName.'/'.$routesPath.'/console.php');

        // Verify content replacements
        $this->assertFileContains(['TestModule'], $this->testModuleName.'/'.$configPath.'/config.php');
        $this->assertFileContains(['TestModuleServiceProvider'], $this->testModuleName.'/'.$providerPath.'/'.$this->testModuleName.'ServiceProvider.php');
    }

    public function test_it_auto_registers_service_provider_when_enabled(): void
    {
        // Create ServiceProvider stub and file
        $this->createTestStub('easymodules/scaffold/service_provider.stub', '<?php class {{ class }} {}');

        $providerPath = 'Providers';
        $this->app['config']->set('easymodules.paths.provider', $providerPath);

        // Create settings with auto_discover enabled
        $settingsWithAutoDiscover = array_merge($this->testSettings, ['auto_discover' => true]);
        $generator                = new ScaffoldModuleFileGenerator($settingsWithAutoDiscover, $this->testModuleName);

        // Create the ServiceProvider file first (simulate successful generation)
        $serviceProviderPath = $this->testBasePath($this->testModuleName.'/'.$providerPath.'/'.$this->testModuleName.'ServiceProvider.php');
        $this->files->ensureDirectoryExists(dirname($serviceProviderPath));
        $this->files->put($serviceProviderPath, '<?php class TestModuleServiceProvider {}');

        $reflection = new \ReflectionClass($generator);
        $method     = $reflection->getMethod('shouldAutoDiscover');
        $method->setAccessible(true);

        $result = $method->invoke($generator);
        $this->assertTrue($result); // Should be true now because file exists and auto_discover is enabled
    }

    public function test_it_handles_missing_stub_files_gracefully(): void
    {
        // Set up generator with non-existent stubs
        $settingsWithBadStubs = array_merge($this->testSettings, [
            'stubs_scaffold' => [
                'config' => 'easymodules/scaffold/nonexistent.stub',
            ],
        ]);

        $configPath = 'config';
        $this->app['config']->set('easymodules.paths.config', $configPath);

        $generator = new ScaffoldModuleFileGenerator($settingsWithBadStubs, $this->testModuleName);

        $result = $generator->generate();

        $this->assertFalse($result); // Should fail due to missing stub

        // Verify failure was logged
        $failedFiles = $generator->getFailedFiles();
        $this->assertNotEmpty($failedFiles);
        $this->assertEquals('config', $failedFiles[0]['type']);
    }

    public function test_it_adds_path_replacements(): void
    {
        // Set explicit paths
        $controllerPath = 'Http/Controllers';
        $modelPath      = 'Models';
        $servicePath    = 'Services';

        $this->app['config']->set('easymodules.paths.controller', $controllerPath);
        $this->app['config']->set('easymodules.paths.model', $modelPath);
        $this->app['config']->set('easymodules.paths.service', $servicePath);

        // Update generator settings to include these paths
        $settingsWithPaths = array_merge($this->testSettings, [
            'paths' => [
                'config'     => 'config',
                'provider'   => 'Providers',
                'routes'     => 'routes',
                'controller' => $controllerPath,
                'model'      => $modelPath,
                'service'    => $servicePath,
            ],
        ]);

        $generator = new ScaffoldModuleFileGenerator($settingsWithPaths, $this->testModuleName);

        $reflection = new \ReflectionClass($generator);
        $method     = $reflection->getMethod('buildReplacementVariables');
        $method->setAccessible(true);

        $result = $method->invoke($generator);

        // Verify path replacements are added
        $this->assertArrayHasKey('{{ controller_path }}', $result);
        $this->assertArrayHasKey('{{ model_path }}', $result);
        $this->assertArrayHasKey('{{ service_path }}', $result);

        $this->assertEquals($controllerPath, $result['{{ controller_path }}']);
        $this->assertEquals($modelPath, $result['{{ model_path }}']);
        $this->assertEquals($servicePath, $result['{{ service_path }}']);
    }

    public function test_it_creates_file_from_stub_with_overwrite_option(): void
    {
        // Create test stub
        $stubContent = 'Original content: {{ name }}';
        $this->createTestStub('test_overwrite.stub', $stubContent);

        $targetPath = $this->testBasePath('test_file.php');
        $replaces   = ['{{ name }}' => 'TestModule'];

        $reflection = new \ReflectionClass($this->generator);
        $method     = $reflection->getMethod('createFileFromStub');
        $method->setAccessible(true);

        // Create file first time
        $result = $method->invoke($this->generator, $targetPath, 'test_overwrite.stub', $replaces, false);
        $this->assertTrue($result);
        $this->assertFilenameExists('test_file.php');

        // Try to create again without overwrite (should skip)
        $result = $method->invoke($this->generator, $targetPath, 'test_overwrite.stub', $replaces, false);
        $this->assertTrue($result); // Returns true but doesn't overwrite

        // Create updated stub
        $newStubContent = 'Updated content: {{ name }}';
        $this->createTestStub('test_overwrite_new.stub', $newStubContent);

        // Try to create again with overwrite
        $result = $method->invoke($this->generator, $targetPath, 'test_overwrite_new.stub', $replaces, true);
        $this->assertTrue($result);
        $this->assertFileContains(['Updated content: TestModule'], 'test_file.php');
    }

    public function test_it_gets_relative_base_path(): void
    {
        $reflection = new \ReflectionClass($this->generator);
        $method     = $reflection->getMethod('getRelativeBasePath');
        $method->setAccessible(true);

        $result = $method->invoke($this->generator);

        // Should return relative path without app_path prefix
        $this->assertStringNotContainsString(app_path(), $result);
        $this->assertStringContainsString($this->testModuleName, $result);
    }

    public function test_it_handles_custom_scaffold_file_path(): void
    {
        // Test custom scaffold type with explicit configuration
        $customPath = 'Custom/Path';

        // Create generator with custom path in settings
        $settingsWithCustomPath = array_merge($this->testSettings, [
            'paths' => array_merge($this->testSettings['paths'], [
                'custom_type' => $customPath,
            ]),
        ]);

        $generatorWithCustomPath = new ScaffoldModuleFileGenerator($settingsWithCustomPath, $this->testModuleName);

        $reflection = new \ReflectionClass($generatorWithCustomPath);
        $method     = $reflection->getMethod('getTargetFilePath');
        $method->setAccessible(true);

        $result   = $method->invoke($generatorWithCustomPath, 'custom_type');
        $expected = $this->testBasePath($this->testModuleName.'/'.$customPath.'/custom_type.php');

        $this->assertEquals($expected, $result);
    }
}
