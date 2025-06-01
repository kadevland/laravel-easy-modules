<?php

declare(strict_types=1);

namespace Kadevland\EasyModules\Tests\Unit\Traits;

use Illuminate\Console\Command;
use Kadevland\EasyModules\Tests\Support\TestCase;
use Symfony\Component\Console\Input\InputArgument;
use Kadevland\EasyModules\Traits\ResolvesModulePathAndNamespaceCommand;
use PHPUnit\Framework\Attributes\Test;

/**
 * Test suite for ResolvesModulePathAndNamespaceCommand Trait
 */
class ResolvesModulePathAndNamespaceCommandTest extends TestCase
{
    protected $traitInstance;

    protected function setUp(): void
    {
        parent::setUp();

        $this->traitInstance = new class () extends Command
        {
            use ResolvesModulePathAndNamespaceCommand;

            protected $type      = 'test';
            protected $arguments = [];

            public function argument($key = null)
            {
                return $key ? $this->arguments[$key] ?? null : $this->arguments;
            }

            public function setMockArgument(string $key, string $value)
            {
                $this->arguments[$key] = $value;
            }

            public function getArguments(): array
            {
                return $this->prepandModuleInput();
            }

            protected function getParentArguments(): array
            {
                return [
                    ['name', InputArgument::REQUIRED, 'The name of the class'],
                ];
            }
        };

        $reflection = new \ReflectionClass($this->traitInstance);
        $property   = $reflection->getProperty('laravel');
        $property->setAccessible(true);
        $property->setValue($this->traitInstance, $this->app);
    }

    // ================================================================================
    // Module Input Resolution Tests
    // ================================================================================

    #[Test]
    public function it_resolves_module_input_to_studly_case(): void
    {
        $cases = [
            'blog-post'    => 'BlogPost',
            'blog_post'    => 'BlogPost',
            'blog'         => 'Blog',
            'BLOG'         => 'BLOG',
            'user-profile' => 'UserProfile',
            'shop_cart'    => 'ShopCart',
        ];

        foreach ($cases as $input => $expected) {
            $this->traitInstance->setMockArgument('module', $input);

            $reflection = new \ReflectionClass($this->traitInstance);
            $method     = $reflection->getMethod('getModuleInput');
            $method->setAccessible(true);

            $result = $method->invoke($this->traitInstance);
            $this->assertEquals($expected, $result, "Failed for input: {$input}");
        }
    }

    #[Test]
    public function it_prepends_module_input_to_arguments(): void
    {
        $result = $this->traitInstance->getArguments();

        $this->assertIsArray($result);
        $this->assertCount(2, $result);

        // Verify that the module argument is first
        $expected = ['module', InputArgument::REQUIRED, 'The name of the module'];
        $this->assertSame($expected, $result[0]);

        // Verify that the parent argument is second
        $expected = ['name', InputArgument::REQUIRED, 'The name of the class'];
        $this->assertSame($expected, $result[1]);
    }

    #[Test]
    public function it_merges_arguments_correctly(): void
    {
        $reflection = new \ReflectionClass($this->traitInstance);
        $method     = $reflection->getMethod('prepandModuleInput');
        $method->setAccessible(true);
        $result = $method->invoke($this->traitInstance);

        $this->assertIsArray($result);
        $this->assertCount(2, $result);

        // Verify the structure of the module argument
        $moduleArg = $result[0];
        $this->assertEquals('module', $moduleArg[0]);
        $this->assertEquals(InputArgument::REQUIRED, $moduleArg[1]);
        $this->assertEquals('The name of the module', $moduleArg[2]);

        // Verify that parent arguments are present
        $nameArg = $result[1];
        $this->assertEquals('name', $nameArg[0]);
        $this->assertEquals(InputArgument::REQUIRED, $nameArg[1]);
        $this->assertEquals('The name of the class', $nameArg[2]);
    }

    // ================================================================================
    // Namespace Resolution Tests
    // ================================================================================

    #[Test]
    public function it_builds_root_module_namespace(): void
    {
        $cases = [
            ['Foo\\Modules', 'Blog', 'Foo\\Modules\\Blog'],
            ['App\\Modules', 'UserProfile', 'App\\Modules\\UserProfile'],
            ['Custom\\Namespace', 'Shop', 'Custom\\Namespace\\Shop'],
        ];

        foreach ($cases as [$baseNamespace, $module, $expected]) {
            $this->app['config']->set('easymodules.base_namespace', $baseNamespace);
            $this->traitInstance->setMockArgument('module', $module);

            $reflection = new \ReflectionClass($this->traitInstance);
            $method     = $reflection->getMethod('rootModuleNamespace');
            $method->setAccessible(true);

            $result = $method->invoke($this->traitInstance);
            $this->assertEquals($expected, $result);
        }
    }

    #[Test]
    public function it_resolves_component_namespace_with_config(): void
    {
        $this->app['config']->set('easymodules.base_namespace', 'App\\Modules');
        $this->app['config']->set('easymodules.paths.controller', 'Foo/Controllers');
        $this->traitInstance->setMockArgument('module', 'Blog');

        $reflection = new \ReflectionClass($this->traitInstance);
        $method     = $reflection->getMethod('moduleNamespace');
        $method->setAccessible(true);

        $result = $method->invoke($this->traitInstance, 'controller', 'Http/Controllers');

        $this->assertEquals('App\\Modules\\Blog\\Foo\\Controllers', $result);
    }

    #[Test]
    public function it_uses_default_namespace_when_component_config_missing(): void
    {
        $this->app['config']->set('easymodules.base_namespace', 'App\\Modules');
        $this->traitInstance->setMockArgument('module', 'Blog');

        $reflection = new \ReflectionClass($this->traitInstance);
        $method     = $reflection->getMethod('moduleNamespace');
        $method->setAccessible(true);

        $result = $method->invoke($this->traitInstance, 'nonexistent', 'Default/Path');

        $this->assertEquals('App\\Modules\\Blog\\Default\\Path', $result);
    }

    #[Test]
    public function it_handles_randomized_configuration(): void
    {
        $this->app['config']->set('easymodules.base_namespace', 'App\\Modules');

        // Generate random module for testing
        $randomModules = ['Kadev', 'Blog', 'Shop', 'User', 'Auth', 'Payment'];
        $randomModule  = $randomModules[array_rand($randomModules)];
        $this->traitInstance->setMockArgument('module', $randomModule);

        // Random path generators
        $randomKeys   = ['alpha', 'beta', 'gamma', 'delta', 'epsilon', 'zeta'];
        $pathSegments = ['Domain', 'Application', 'Infrastructure', 'Presentation', 'Database'];
        $subSegments  = ['Models', 'Services', 'Controllers', 'Entities', 'Repositories', 'Jobs'];

        // Generate completely randomized paths
        $randomizedPaths = [];
        for ($i = 0; $i < 6; $i++) {
            $randomKey                   = $randomKeys[array_rand($randomKeys)].'_'.uniqid();
            $randomPath                  = $pathSegments[array_rand($pathSegments)].'/'.$subSegments[array_rand($subSegments)];
            $randomizedPaths[$randomKey] = $randomPath;
        }

        // Configure all randomized paths
        foreach ($randomizedPaths as $key => $path) {
            $this->app['config']->set("easymodules.paths.{$key}", $path);
        }

        $reflection = new \ReflectionClass($this->traitInstance);
        $method     = $reflection->getMethod('moduleNamespace');
        $method->setAccessible(true);

        // Test each randomized path configuration
        foreach ($randomizedPaths as $key => $path) {
            $result   = $method->invoke($this->traitInstance, $key, $path);
            $expected = "App\\Modules\\{$randomModule}\\".str_replace('/', '\\', $path);

            $this->assertEquals(
                $expected,
                $result,
                "Failed for module: {$randomModule}, random key: {$key} with random path: {$path}"
            );
        }
    }

    // ================================================================================
    // Path Resolution Tests
    // ================================================================================

    #[Test]
    public function it_builds_root_module_path(): void
    {
        $cases = [
            ['/foo/Modules', 'Blog', '/foo/Modules/Blog'],
            ['/app/Modules', 'UserProfile', '/app/Modules/UserProfile'],
            ['/custom/path', 'Shop', '/custom/path/Shop'],
        ];

        foreach ($cases as [$basePath, $module, $expected]) {
            $this->app['config']->set('easymodules.base_path', $basePath);
            $this->traitInstance->setMockArgument('module', $module);

            $reflection = new \ReflectionClass($this->traitInstance);
            $method     = $reflection->getMethod('rootModulePath');
            $method->setAccessible(true);

            $result = $method->invoke($this->traitInstance);
            $this->assertEquals($expected, $result);
        }
    }

    #[Test]
    public function it_resolves_component_path_with_config(): void
    {
        $this->app['config']->set('easymodules.paths.controller', 'Foo/Controllers');
        $this->traitInstance->setMockArgument('module', 'Blog');

        $reflection = new \ReflectionClass($this->traitInstance);
        $method     = $reflection->getMethod('modulePath');
        $method->setAccessible(true);

        $result = $method->invoke($this->traitInstance, 'controller', 'Http/Controllers');

        $this->assertEquals($this->testBasePath('Blog/Foo/Controllers'), $result);
    }

    #[Test]
    public function it_uses_default_path_when_component_config_missing(): void
    {
        $this->traitInstance->setMockArgument('module', 'Blog');

        $reflection = new \ReflectionClass($this->traitInstance);
        $method     = $reflection->getMethod('modulePath');
        $method->setAccessible(true);

        $result = $method->invoke($this->traitInstance, 'nonexistent', 'Default/Path');

        $this->assertEquals($this->testBasePath('Blog/Default/Path'), $result);
    }

    #[Test]
    public function it_builds_view_path(): void
    {
        $this->app['config']->set('easymodules.paths.view', 'Presentation/resources/views');
        $this->traitInstance->setMockArgument('module', 'Blog');

        $reflection = new \ReflectionClass($this->traitInstance);
        $method     = $reflection->getMethod('viewPath');
        $method->setAccessible(true);

        // Test without additional path
        $result   = $method->invoke($this->traitInstance);
        $expected = $this->testBasePath('Blog/Presentation/resources/views');
        $this->assertEquals($expected, $result);

        // Test with additional path
        $result   = $method->invoke($this->traitInstance, 'components');
        $expected = $this->testBasePath('Blog/Presentation/resources/views'.DIRECTORY_SEPARATOR.'components');
        $this->assertEquals($expected, $result);
    }

    // ================================================================================
    // Inherited Functionality Tests
    // ================================================================================

    #[Test]
    public function it_inherits_path_namespace_converter_functionality(): void
    {
        // Test that the trait inherits PathNamespaceConverter methods
        $this->assertTrue(method_exists($this->traitInstance, 'pathToNamespace'));
        $this->assertTrue(method_exists($this->traitInstance, 'toStudlyNamespace'));
        $this->assertTrue(method_exists($this->traitInstance, 'generatePhpFilePath'));
        $this->assertTrue(method_exists($this->traitInstance, 'normalizePath'));

        // Test an inherited method
        $reflection = new \ReflectionClass($this->traitInstance);
        $method     = $reflection->getMethod('pathToNamespace');
        $method->setAccessible(true);

        $result = $method->invoke($this->traitInstance, 'Domain/Entities');
        $this->assertEquals('Domain\\Entities', $result);
    }

    #[Test]
    public function it_inherits_manages_suffixes_functionality(): void
    {
        // Test that the trait inherits ManagesSuffixes methods
        $this->assertTrue(method_exists($this->traitInstance, 'addSuffixIfMissing'));

        // Test an inherited method
        $reflection = new \ReflectionClass($this->traitInstance);
        $method     = $reflection->getMethod('addSuffixIfMissing');
        $method->setAccessible(true);

        $result = $method->invoke($this->traitInstance, 'User', 'Controller');
        $this->assertEquals('UserController', $result);
    }

    #[Test]
    public function it_inherits_php_file_path_generation_functionality(): void
    {
        // Test that the trait inherits PHP file path generation methods
        $this->assertTrue(method_exists($this->traitInstance, 'generatePhpFilePath'));

        // Test an inherited method
        $reflection = new \ReflectionClass($this->traitInstance);
        $method     = $reflection->getMethod('generatePhpFilePath');
        $method->setAccessible(true);

        $result = $method->invoke($this->traitInstance, 'UserController');
        $this->assertEquals('UserController.php', $result);
    }
}
