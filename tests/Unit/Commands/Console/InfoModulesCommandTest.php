<?php

declare(strict_types=1);

namespace Kadevland\EasyModules\Tests\Unit\Commands\Console;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Kadevland\EasyModules\Tests\Support\TestCase;
use Kadevland\EasyModules\Commands\Console\InfoModulesCommand;

class InfoModulesCommandTest extends TestCase
{
    protected InfoModulesCommand $command;
    protected Filesystem         $files;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup configuration first
        $this->setupTestConfig();

        // Create filesystem mock
        $this->files = $this->createMock(Filesystem::class);

        // Create command with mocked filesystem
        $this->command = new InfoModulesCommand($this->files);

        // Setup console I/O for command
        $output = new \Symfony\Component\Console\Output\BufferedOutput();
        $input  = new \Symfony\Component\Console\Input\ArrayInput([]);
        $style  = new \Illuminate\Console\OutputStyle($input, $output);
        $this->command->setOutput($style);

        // Initialize command properties after creation
        $this->initializeCommandProperties();
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // CONFIGURATION & HELPERS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    protected function setupTestConfig(): void
    {
        $this->app['config']->set('easymodules.base_path', '/app/Modules');
        $this->app['config']->set('easymodules.base_namespace', 'App\\Modules');
        $this->app['config']->set('easymodules.paths', [
            'provider' => 'Providers',
            'routes'   => 'routes',
        ]);
        $this->app['config']->set('easymodules.test_paths', [
            'unit'    => 'Tests/Unit',
            'feature' => 'Tests/Feature',
        ]);
        $this->app['config']->set('easymodules.scaffold', ['Providers', 'config', 'routes']);
        $this->app['config']->set('easymodules.folders_to_generate', [
            'Application/Services',
            'Domain/Entities',
            'Infrastructure/Models',
        ]);
    }

    protected function initializeCommandProperties(): void
    {
        $reflection = new \ReflectionClass($this->command);

        $properties = [
            'basePath'          => '/app/Modules',
            'baseNamespace'     => 'App\\Modules',
            'paths'             => ['provider' => 'Providers', 'routes' => 'routes'],
            'testPaths'         => ['unit' => 'Tests/Unit', 'feature' => 'Tests/Feature'],
            'scaffoldFolders'   => ['Providers', 'config', 'routes'],
            'configuredFolders' => ['Application/Services', 'Domain/Entities', 'Infrastructure/Models'],
        ];

        foreach ($properties as $propertyName => $value) {
            $property = $reflection->getProperty($propertyName);
            $property->setAccessible(true);
            $property->setValue($this->command, $value);
        }
    }

    protected function createPartialCommandMock(array $methods): InfoModulesCommand
    {
        $filesMock = $this->createMock(Filesystem::class);
        $mock      = $this->createPartialMock(InfoModulesCommand::class, $methods);

        // Set filesystem dependency
        $reflection    = new \ReflectionClass($mock);
        $filesProperty = $reflection->getProperty('files');
        $filesProperty->setAccessible(true);
        $filesProperty->setValue($mock, $filesMock);

        // Setup console I/O for mocked command
        $output = new \Symfony\Component\Console\Output\BufferedOutput();
        $input  = new \Symfony\Component\Console\Input\ArrayInput([]);
        $style  = new \Illuminate\Console\OutputStyle($input, $output);
        $mock->setOutput($style);

        $this->initializeCommandMockProperties($mock);
        return $mock;
    }

    protected function initializeCommandMockProperties($mock): void
    {
        $reflection = new \ReflectionClass($mock);

        $properties = [
            'basePath'          => '/app/Modules',
            'baseNamespace'     => 'App\\Modules',
            'paths'             => ['provider' => 'Providers', 'routes' => 'routes'],
            'testPaths'         => ['unit' => 'Tests/Unit', 'feature' => 'Tests/Feature'],
            'scaffoldFolders'   => ['Providers', 'config', 'routes'],
            'configuredFolders' => ['Application/Services', 'Domain/Entities', 'Infrastructure/Models'],
        ];

        foreach ($properties as $propertyName => $value) {
            $property = $reflection->getProperty($propertyName);
            $property->setAccessible(true);
            $property->setValue($mock, $value);
        }
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // NIVEAU 5 : TESTS UTILITAIRES (Input/Output Purs)
    // ═══════════════════════════════════════════════════════════════════════════════════════

    #[\PHPUnit\Framework\Attributes\DataProvider('utilityMethodsProvider')]
    public function test_utility_methods(string $method, string $input, string $expected): void
    {
        $reflection = new \ReflectionClass($this->command);
        $method     = $reflection->getMethod($method);
        $method->setAccessible(true);

        $result = $method->invoke($this->command, $input);

        $this->assertEquals($expected, $result);
    }

    public static function utilityMethodsProvider(): array
    {
        return [
            'module_path'            => ['getModulePath', 'Blog', '/app/Modules/Blog'],
            'module_namespace'       => ['getModuleNamespace', 'Blog', 'App\\Modules\\Blog'],
            'service_provider_class' => ['getServiceProviderClass', 'Blog', 'App\\Modules\\Blog\\Providers\\BlogServiceProvider'],
            'module_path_complex'    => ['getModulePath', 'UserProfile', '/app/Modules/UserProfile'],
            'namespace_complex'      => ['getModuleNamespace', 'UserProfile', 'App\\Modules\\UserProfile'],
        ];
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // NIVEAU 4 : TESTS D'ALGORITHMES PURS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    #[\PHPUnit\Framework\Attributes\DataProvider('classifyFoldersProvider')]
    public function test_classify_folders_algorithm(array $existingFolders, array $expectedClassification): void
    {
        $reflection = new \ReflectionClass($this->command);
        $method     = $reflection->getMethod('classifyFolders');
        $method->setAccessible(true);

        $result = $method->invoke($this->command, $existingFolders);

        $this->assertEquals($expectedClassification, $result);
    }

    public static function classifyFoldersProvider(): array
    {
        return [
            'complete_structure'             => [
                // Existing folders (sorted)
                ['Application/Services', 'config', 'Custom/Helper', 'Domain/Entities', 'Infrastructure/Models', 'Providers', 'routes', 'Tests/Unit'],
                // Expected classification
                [
                    'scaffold'           => ['config', 'Providers', 'routes'], // Sorted alphabetically
                    'generated'          => ['Application/Services', 'Domain/Entities', 'Infrastructure/Models'],
                    'paths'              => [],
                    'test_paths'         => ['Tests/Unit'],
                    'additional'         => ['Custom/Helper'],
                    'missing_scaffold'   => [],
                    'missing_generated'  => [],
                    'missing_paths'      => [],
                    'missing_test_paths' => ['Tests/Feature'], // Tests/Feature is missing
                ],
            ],
            'partial_structure_with_missing' => [
                ['Application/Services', 'config', 'Legacy/Old', 'Providers', 'Tests/Feature'],
                [
                    'scaffold'           => ['config', 'Providers'], // Sorted alphabetically
                    'generated'          => ['Application/Services'],
                    'paths'              => [],
                    'test_paths'         => ['Tests/Feature'],
                    'additional'         => ['Legacy/Old'],
                    'missing_scaffold'   => ['routes'],
                    'missing_generated'  => ['Domain/Entities', 'Infrastructure/Models'],
                    'missing_paths'      => ['routes'], // 'routes' is also in paths config
                    'missing_test_paths' => ['Tests/Unit'], // Tests/Unit is missing
                ],
            ],
            'empty_structure'                => [
                [],
                [
                    'scaffold'           => [],
                    'generated'          => [],
                    'paths'              => [],
                    'test_paths'         => [],
                    'additional'         => [],
                    'missing_scaffold'   => ['Providers', 'config', 'routes'],
                    'missing_generated'  => ['Application/Services', 'Domain/Entities', 'Infrastructure/Models'],
                    'missing_paths'      => ['Providers', 'routes'], // Some paths configs reference paths that are also in scaffold
                    'missing_test_paths' => ['Tests/Unit', 'Tests/Feature'],
                ],
            ],
            'only_additional_folders'        => [
                ['Custom/Library', 'Legacy/Support'],
                [
                    'scaffold'           => [],
                    'generated'          => [],
                    'paths'              => [],
                    'test_paths'         => [],
                    'additional'         => ['Custom/Library', 'Legacy/Support'],
                    'missing_scaffold'   => ['Providers', 'config', 'routes'],
                    'missing_generated'  => ['Application/Services', 'Domain/Entities', 'Infrastructure/Models'],
                    'missing_paths'      => ['Providers', 'routes'], // Some paths configs reference paths that are also in scaffold
                    'missing_test_paths' => ['Tests/Unit', 'Tests/Feature'],
                ],
            ],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('serviceProviderDetectionProvider')]
    public function test_service_provider_detection(string $content, string $fullClass, string $baseClass, bool $expected): void
    {
        $reflection = new \ReflectionClass($this->command);
        $method     = $reflection->getMethod('isProviderInContent');
        $method->setAccessible(true);

        $result = $method->invoke($this->command, $content, $fullClass, $baseClass);

        $this->assertEquals($expected, $result);
    }

    public static function serviceProviderDetectionProvider(): array
    {
        return [
            'full_namespace_class_format'           => [
                '<?php return [App\Modules\Blog\Providers\BlogServiceProvider::class];',
                'App\Modules\Blog\Providers\BlogServiceProvider',
                'BlogServiceProvider',
                true,
            ],
            'use_statement_with_class'              => [
                "<?php\nuse App\Modules\Blog\Providers\BlogServiceProvider;\nreturn [BlogServiceProvider::class];",
                'App\Modules\Blog\Providers\BlogServiceProvider',
                'BlogServiceProvider',
                true,
            ],
            'string_format_single_quotes'           => [
                "<?php return ['App\Modules\Blog\Providers\BlogServiceProvider'];",
                'App\Modules\Blog\Providers\BlogServiceProvider',
                'BlogServiceProvider',
                true,
            ],
            'string_format_double_quotes'           => [
                '<?php return ["App\Modules\Blog\Providers\BlogServiceProvider"];',
                'App\Modules\Blog\Providers\BlogServiceProvider',
                'BlogServiceProvider',
                true,
            ],
            'false_positive_similar_name'           => [
                '<?php return [App\Modules\BlogExtended\Providers\BlogExtendedServiceProvider::class];',
                'App\Modules\Blog\Providers\BlogServiceProvider',
                'BlogServiceProvider',
                false,
            ],
            'partial_match_should_fail'             => [
                '<?php return [App\Modules\Blog\Providers\OtherBlogServiceProvider::class];',
                'App\Modules\Blog\Providers\BlogServiceProvider',
                'BlogServiceProvider',
                false,
            ],
            'not_found'                             => [
                '<?php return [App\Providers\AppServiceProvider::class];',
                'App\Modules\Blog\Providers\BlogServiceProvider',
                'BlogServiceProvider',
                false,
            ],
            'use_statement_without_class_reference' => [
                "<?php\nuse App\Modules\Blog\Providers\BlogServiceProvider;\nreturn [App\Providers\AppServiceProvider::class];",
                'App\Modules\Blog\Providers\BlogServiceProvider',
                'BlogServiceProvider',
                false,
            ],
        ];
    }

    public function test_has_use_statement_and_class_detection(): void
    {
        $reflection = new \ReflectionClass($this->command);
        $method     = $reflection->getMethod('hasUseStatementAndClass');
        $method->setAccessible(true);

        $content = "<?php\nuse App\Modules\Blog\Providers\BlogServiceProvider;\nreturn [BlogServiceProvider::class];";
        $result  = $method->invoke($this->command, $content, 'App\Modules\Blog\Providers\BlogServiceProvider', 'BlogServiceProvider');

        $this->assertTrue($result);

        // Test without class reference
        $contentNoClass = "<?php\nuse App\Modules\Blog\Providers\BlogServiceProvider;\nreturn [App\Providers\AppServiceProvider::class];";
        $result         = $method->invoke($this->command, $contentNoClass, 'App\Modules\Blog\Providers\BlogServiceProvider', 'BlogServiceProvider');

        $this->assertFalse($result);
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // NIVEAU 3 : TESTS BUSINESS LOGIC AVEC SCÉNARIOS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    public function test_get_module_data_complete_scenario(): void
    {
        // Module Blog - Structure complète
        $command = $this->createPartialCommandMock([
            'getAllExistingFolders',
            'getRegistrationData',
        ]);

        $existingFolders = [
            'Application/Services',
            'config',
            'Custom/Helpers',
            'Domain/Entities',
            'Infrastructure/Models',
            'Providers',
            'routes',
            'Tests/Unit',
        ];

        $registrationData = [
            'service_provider' => [
                'class'      => 'BlogServiceProvider',
                'exists'     => true,
                'registered' => true,
            ],
            'routes'           => [
                'web'     => true,
                'api'     => true,
                'console' => false,
            ],
        ];

        $command->expects($this->once())
            ->method('getAllExistingFolders')
            ->with('/app/Modules/Blog')
            ->willReturn($existingFolders);

        $command->expects($this->once())
            ->method('getRegistrationData')
            ->with('Blog', '/app/Modules/Blog')
            ->willReturn($registrationData);

        // Use reflection to call protected method
        $reflection = new \ReflectionClass($command);
        $method     = $reflection->getMethod('getModuleData');
        $method->setAccessible(true);

        $result = $method->invoke($command, 'Blog');

        $expected = [
            'name'         => 'Blog',
            'base_path'    => '/app/Modules/Blog',
            'namespace'    => 'App\\Modules\\Blog',
            'exists'       => false, // Files mock pas configuré, pas important pour ce test
            'folders'      => [
                'all_existing'       => $existingFolders,
                'scaffold'           => ['config', 'Providers', 'routes'], // Sorted alphabetically
                'generated'          => ['Application/Services', 'Domain/Entities', 'Infrastructure/Models'],
                'paths'              => [],
                'test_paths'         => ['Tests/Unit'],
                'additional'         => ['Custom/Helpers'],
                'missing_scaffold'   => [],
                'missing_generated'  => [],
                'missing_paths'      => [],
                'missing_test_paths' => ['Tests/Feature'], // Tests/Feature is missing
            ],
            'registration' => $registrationData,
        ];

        $this->assertEquals($expected, $result);
    }

    public function test_get_module_data_partial_scenario(): void
    {
        // Module User - Structure partielle avec éléments manquants
        $command = $this->createPartialCommandMock([
            'getAllExistingFolders',
            'getRegistrationData',
        ]);

        $existingFolders = [
            'Application/Services',
            'config',
            'Legacy/Old',
            'Providers',
            'Tests/Feature' // Différent de la config qui attend Tests/Unit
        ];

        $registrationData = [
            'service_provider' => [
                'class'      => 'UserServiceProvider',
                'exists'     => true,
                'registered' => false,
            ],
            'routes'           => [
                'web'     => false,
                'api'     => false,
                'console' => false,
            ],
        ];

        $command->expects($this->once())
            ->method('getAllExistingFolders')
            ->with('/app/Modules/User')
            ->willReturn($existingFolders);

        $command->expects($this->once())
            ->method('getRegistrationData')
            ->with('User', '/app/Modules/User')
            ->willReturn($registrationData);

        // Use reflection to call protected method
        $reflection = new \ReflectionClass($command);
        $method     = $reflection->getMethod('getModuleData');
        $method->setAccessible(true);

        $result = $method->invoke($command, 'User');

        $expected = [
            'name'         => 'User',
            'base_path'    => '/app/Modules/User',
            'namespace'    => 'App\\Modules\\User',
            'exists'       => null, // files->isDirectory not mocked, returns null
            'folders'      => [
                'all_existing'       => $existingFolders,
                'scaffold'           => ['config', 'Providers'], // Sorted alphabetically
                'generated'          => ['Application/Services'],
                'paths'              => [],
                'test_paths'         => ['Tests/Feature'],
                'additional'         => ['Legacy/Old'],
                'missing_scaffold'   => ['routes'],
                'missing_generated'  => ['Domain/Entities', 'Infrastructure/Models'],
                'missing_paths'      => ['routes'], // 'routes' is also in paths config
                'missing_test_paths' => ['Tests/Unit'], // Tests/Unit is missing
            ],
            'registration' => $registrationData,
        ];

        $this->assertEquals($expected, $result);
    }

    public function test_get_registration_data(): void
    {
        $command = $this->createPartialCommandMock([
            'hasServiceProvider',
            'isServiceProviderRegistered',
            'getRoutesInfo',
        ]);

        $command->expects($this->once())
            ->method('hasServiceProvider')
            ->with('/app/Modules/Blog', 'Blog')
            ->willReturn(true);

        $command->expects($this->once())
            ->method('isServiceProviderRegistered')
            ->with('Blog')
            ->willReturn(true);

        $command->expects($this->once())
            ->method('getRoutesInfo')
            ->with('/app/Modules/Blog')
            ->willReturn(['web' => true, 'api' => false, 'console' => true]);

        $reflection = new \ReflectionClass($command);
        $method     = $reflection->getMethod('getRegistrationData');
        $method->setAccessible(true);

        $result = $method->invoke($command, 'Blog', '/app/Modules/Blog');

        $expected = [
            'service_provider' => [
                'class'      => 'BlogServiceProvider',
                'exists'     => true,
                'registered' => true,
            ],
            'routes'           => [
                'web'     => true,
                'api'     => false,
                'console' => true,
            ],
        ];

        $this->assertEquals($expected, $result);
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // NIVEAU 2 : TESTS DE DISCOVERY
    // ═══════════════════════════════════════════════════════════════════════════════════════

    public function test_discover_all_modules_filters_valid_providers(): void
    {
        // Use reflection to access protected method
        $reflection = new \ReflectionClass($this->command);
        $method     = $reflection->getMethod('discoverAllModules');
        $method->setAccessible(true);

        $this->files->expects($this->once())
            ->method('isDirectory')
            ->with('/app/Modules')
            ->willReturn(true);

        $this->files->expects($this->once())
            ->method('directories')
            ->with('/app/Modules')
            ->willReturn(['/app/Modules/Blog', '/app/Modules/Invalid', '/app/Modules/User']);

        // Mock hasServiceProvider calls
        $this->files->expects($this->exactly(3))
            ->method('exists')
            ->willReturnMap([
                ['/app/Modules/Blog/Providers/BlogServiceProvider.php', true],
                ['/app/Modules/Invalid/Providers/InvalidServiceProvider.php', false],
                ['/app/Modules/User/Providers/UserServiceProvider.php', true],
            ]);

        $result = $method->invoke($this->command);

        $this->assertCount(2, $result);
        $this->assertEquals('Blog', $result[0]['name']);
        $this->assertEquals('User', $result[1]['name']);
        $this->assertEquals('/app/Modules/Blog', $result[0]['path']);
        $this->assertEquals('/app/Modules/User', $result[1]['path']);
    }

    public function test_discover_all_modules_empty_directory(): void
    {
        $this->files->expects($this->once())
            ->method('isDirectory')
            ->with('/app/Modules')
            ->willReturn(false);

        $reflection = new \ReflectionClass($this->command);
        $method     = $reflection->getMethod('discoverAllModules');
        $method->setAccessible(true);

        $result = $method->invoke($this->command);

        $this->assertEmpty($result);
    }

    public function test_module_exists(): void
    {
        // Use reflection to access protected method
        $reflection = new \ReflectionClass($this->command);
        $method     = $reflection->getMethod('moduleExists');
        $method->setAccessible(true);

        $this->files->expects($this->once())
            ->method('isDirectory')
            ->with('/app/Modules/Blog')
            ->willReturn(true);

        $this->files->expects($this->once())
            ->method('exists')
            ->with('/app/Modules/Blog/Providers/BlogServiceProvider.php')
            ->willReturn(true);

        $result = $method->invoke($this->command, 'Blog');

        $this->assertTrue($result);
    }

    public function test_has_service_provider(): void
    {
        $this->files->expects($this->once())
            ->method('exists')
            ->with('/app/Modules/Blog/Providers/BlogServiceProvider.php')
            ->willReturn(true);

        $reflection = new \ReflectionClass($this->command);
        $method     = $reflection->getMethod('hasServiceProvider');
        $method->setAccessible(true);

        $result = $method->invoke($this->command, '/app/Modules/Blog', 'Blog');

        $this->assertTrue($result);
    }

    public function test_get_routes_info(): void
    {
        $this->files->expects($this->exactly(3))
            ->method('exists')
            ->willReturnMap([
                ['/app/Modules/Blog/routes/web.php', true],
                ['/app/Modules/Blog/routes/api.php', false],
                ['/app/Modules/Blog/routes/console.php', true],
            ]);

        $reflection = new \ReflectionClass($this->command);
        $method     = $reflection->getMethod('getRoutesInfo');
        $method->setAccessible(true);

        $result = $method->invoke($this->command, '/app/Modules/Blog');

        $expected = [
            'web'     => true,
            'api'     => false,
            'console' => true,
        ];

        $this->assertEquals($expected, $result);
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // TESTS SERVICE PROVIDER REGISTRATION
    // ═══════════════════════════════════════════════════════════════════════════════════════

    public function test_is_service_provider_registered_file_not_exists(): void
    {
        // Get real bootstrap path from Laravel app
        $realBootstrapPath = $this->app->getBootstrapProvidersPath();

        $this->files->expects($this->once())
            ->method('exists')
            ->with($realBootstrapPath)
            ->willReturn(false);

        $reflection = new \ReflectionClass($this->command);
        $method     = $reflection->getMethod('isServiceProviderRegistered');
        $method->setAccessible(true);

        $result = $method->invoke($this->command, 'Blog');

        $this->assertFalse($result);
    }

    public function test_is_service_provider_registered_found(): void
    {
        $command = $this->createPartialCommandMock(['getBootstrapProvidersContent']);

        // Get real bootstrap path from Laravel app
        $realBootstrapPath = $this->app->getBootstrapProvidersPath();

        // Get filesystem mock from command
        $reflection    = new \ReflectionClass($command);
        $filesProperty = $reflection->getProperty('files');
        $filesProperty->setAccessible(true);
        $filesMock = $filesProperty->getValue($command);

        $filesMock->expects($this->once())
            ->method('exists')
            ->with($realBootstrapPath)
            ->willReturn(true);

        $content = '<?php return [App\Modules\Blog\Providers\BlogServiceProvider::class];';

        $command->expects($this->once())
            ->method('getBootstrapProvidersContent')
            ->with($realBootstrapPath)
            ->willReturn($content);

        $method = $reflection->getMethod('isServiceProviderRegistered');
        $method->setAccessible(true);

        $result = $method->invoke($command, 'Blog');

        $this->assertTrue($result);
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // TESTS EDGE CASES
    // ═══════════════════════════════════════════════════════════════════════════════════════

    public function test_get_all_existing_folders_empty_module(): void
    {
        $this->files->expects($this->once())
            ->method('isDirectory')
            ->with('/app/Modules/EmptyModule')
            ->willReturn(false);

        $reflection = new \ReflectionClass($this->command);
        $method     = $reflection->getMethod('getAllExistingFolders');
        $method->setAccessible(true);

        $result = $method->invoke($this->command, '/app/Modules/EmptyModule');

        $this->assertEmpty($result);
    }

    public function test_get_all_existing_folders_with_directories(): void
    {
        $this->files->expects($this->once())
            ->method('isDirectory')
            ->with('/app/Modules/Blog')
            ->willReturn(true);

        $this->files->expects($this->once())
            ->method('directories')
            ->with('/app/Modules/Blog')
            ->willReturn([
                '/app/Modules/Blog/config',
                '/app/Modules/Blog/Providers',
                '/app/Modules/Blog/Application',
            ]);

        $reflection = new \ReflectionClass($this->command);
        $method     = $reflection->getMethod('getAllExistingFolders');
        $method->setAccessible(true);

        $result = $method->invoke($this->command, '/app/Modules/Blog');

        $expected = ['Application', 'Providers', 'config']; // Sorted
        $this->assertEquals($expected, $result);
    }

    public function test_classify_folders_with_conflicts(): void
    {
        // Test when a folder could be in multiple categories
        // (This shouldn't happen with good config, but test edge case)

        // Temporarily modify config to create overlap
        $reflection = new \ReflectionClass($this->command);
        $property   = $reflection->getProperty('scaffoldFolders');
        $property->setAccessible(true);
        $property->setValue($this->command, ['Providers', 'Tests/Unit']); // Overlap with testPaths

        $method = $reflection->getMethod('classifyFolders');
        $method->setAccessible(true);

        $result = $method->invoke($this->command, ['Providers', 'Tests/Unit']);

        // Should prioritize scaffold over test_paths (first match wins)
        $this->assertContains('Providers', $result['scaffold']);
        $this->assertContains('Tests/Unit', $result['scaffold']);
        $this->assertEmpty($result['test_paths']);
    }
}
