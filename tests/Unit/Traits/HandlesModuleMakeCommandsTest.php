<?php

declare(strict_types=1);

namespace Kadevland\EasyModules\Tests\Unit\Traits;

use Illuminate\Console\Command;
use Kadevland\EasyModules\Tests\Support\TestCase;
use Kadevland\EasyModules\Traits\HandlesModuleMakeCommands;
use PHPUnit\Framework\Attributes\Test;

/**
 * Test suite for HandlesModuleMakeCommands Trait
 */
class HandlesModuleMakeCommandsTest extends TestCase
{
    protected $traitInstance;

    protected function setUp(): void
    {
        parent::setUp();

        $this->traitInstance = new class () extends Command
        {
            use HandlesModuleMakeCommands;

            protected $componentType = 'controller';
            protected $name          = 'test:make-controller';
            protected $description   = 'Test command';
            protected $arguments     = [];
            protected $options       = [];

            public function argument($key = null)
            {
                return $key ? $this->arguments[$key] ?? null : $this->arguments;
            }

            public function option($key = null)
            {
                return $key ? $this->options[$key] ?? null : $this->options;
            }

            public function setMockArgument(string $key, string $value)
            {
                $this->arguments[$key] = $value;
            }

            public function setMockOption(string $key, $value)
            {
                $this->options[$key] = $value;
            }

            public function call($command, array $arguments = [])
            {
                return 0; // Success
            }
        };

        $reflection = new \ReflectionClass($this->traitInstance);
        $property   = $reflection->getProperty('laravel');
        $property->setAccessible(true);
        $property->setValue($this->traitInstance, $this->app);
    }

    // ================================================================================
    // Component Type Methods
    // ================================================================================

    #[Test]
    public function it_gets_component_type(): void
    {
        $reflection = new \ReflectionClass($this->traitInstance);
        $method     = $reflection->getMethod('getComponentType');
        $method->setAccessible(true);

        $result = $method->invoke($this->traitInstance);
        $this->assertEquals('controller', $result);
    }

    #[Test]
    public function it_gets_component_type_from_class_name_when_property_missing(): void
    {
        // Test fallback behavior when componentType property doesn't exist
        $traitInstanceWithoutProperty = new class () extends Command
        {
            use HandlesModuleMakeCommands;
            // No $componentType property - will use fallback
        };

        $reflection = new \ReflectionClass($traitInstanceWithoutProperty);
        $property   = $reflection->getProperty('laravel');
        $property->setAccessible(true);
        $property->setValue($traitInstanceWithoutProperty, $this->app);

        $method = $reflection->getMethod('getComponentType');
        $method->setAccessible(true);

        $result = $method->invoke($traitInstanceWithoutProperty);

        // Test that fallback works - returns a non-empty string
        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }

    // ================================================================================
    // Name Input Processing
    // ================================================================================

    #[Test]
    public function it_gets_name_input_with_studly_case(): void
    {
        $this->traitInstance->setMockArgument('name', 'user-profile');

        $reflection = new \ReflectionClass($this->traitInstance);
        $method     = $reflection->getMethod('getNameInput');
        $method->setAccessible(true);

        $result = $method->invoke($this->traitInstance);
        // Le trait produit 'User/Profile' avec toStudlyNamespace
        $this->assertEquals('User/Profile', $result);
    }

    #[Test]
    public function it_gets_name_input_with_nested_namespaces(): void
    {
        $this->traitInstance->setMockArgument('name', 'admin/user-profile');

        $reflection = new \ReflectionClass($this->traitInstance);
        $method     = $reflection->getMethod('getNameInput');
        $method->setAccessible(true);

        $result = $method->invoke($this->traitInstance);
        // Le trait produit 'Admin/User/Profile' avec toStudlyNamespace
        $this->assertEquals('Admin/User/Profile', $result);
    }

    // ================================================================================
    // Suffix Management
    // ================================================================================

    #[Test]
    public function it_checks_suffix_configuration(): void
    {
        // Test when suffix should not be appended
        $this->app['config']->set('easymodules.append_suffix', false);
        $reflection = new \ReflectionClass($this->traitInstance);
        $method     = $reflection->getMethod('shouldAppendSuffix');
        $method->setAccessible(true);

        $result = $method->invoke($this->traitInstance);
        $this->assertFalse($result);

        // Test when suffix should be appended
        $this->app['config']->set('easymodules.append_suffix', true);
        $result = $method->invoke($this->traitInstance);
        $this->assertTrue($result);
    }

    #[Test]
    public function it_gets_suffix_for_type(): void
    {
        $this->app['config']->set('easymodules.suffixes.controller', 'Controller');
        $this->app['config']->set('easymodules.suffixes.service', 'Service');

        $reflection = new \ReflectionClass($this->traitInstance);
        $method     = $reflection->getMethod('getSuffixForType');
        $method->setAccessible(true);

        $result = $method->invoke($this->traitInstance, 'controller');
        $this->assertEquals('Controller', $result);

        $result = $method->invoke($this->traitInstance, 'service');
        $this->assertEquals('Service', $result);

        $result = $method->invoke($this->traitInstance, 'nonexistent');
        $this->assertEquals('', $result);
    }

    #[Test]
    public function it_adds_suffix_when_configured(): void
    {
        $this->app['config']->set('easymodules.append_suffix', true);
        $this->app['config']->set('easymodules.suffixes.controller', 'Controller');

        $this->traitInstance->setMockArgument('name', 'User');

        $reflection = new \ReflectionClass($this->traitInstance);
        $method     = $reflection->getMethod('getNameInput');
        $method->setAccessible(true);

        $result = $method->invoke($this->traitInstance);
        $this->assertEquals('UserController', $result);
    }

    #[Test]
    public function it_does_not_add_suffix_when_already_present(): void
    {
        $this->app['config']->set('easymodules.append_suffix', true);
        $this->app['config']->set('easymodules.suffixes.controller', 'Controller');

        $this->traitInstance->setMockArgument('name', 'UserController');

        $reflection = new \ReflectionClass($this->traitInstance);
        $method     = $reflection->getMethod('getNameInput');
        $method->setAccessible(true);

        $result = $method->invoke($this->traitInstance);
        $this->assertEquals('UserController', $result);
    }

    // ================================================================================
    // Namespace Methods
    // ================================================================================

    #[Test]
    public function it_gets_root_namespace(): void
    {
        $this->app['config']->set('easymodules.base_namespace', 'App\\Modules');
        $this->traitInstance->setMockArgument('module', 'Blog');

        $reflection = new \ReflectionClass($this->traitInstance);
        $method     = $reflection->getMethod('rootNamespace');
        $method->setAccessible(true);

        $result = $method->invoke($this->traitInstance);
        $this->assertEquals('App\\Modules\\Blog', $result);
    }

    // ================================================================================
    // Component Discovery
    // ================================================================================

    #[Test]
    public function it_gets_module_components(): void
    {
        // Create a temporary directory structure for testing
        $testPath = $this->testBasePath('Blog/Presentation/Http/Controllers');
        if (! is_dir($testPath)) {
            mkdir($testPath, 0755, true);
        }

        // Create test files
        file_put_contents($testPath.'/PostController.php', '<?php class PostController {}');
        file_put_contents($testPath.'/UserController.php', '<?php class UserController {}');
        file_put_contents($testPath.'/AbstractController.php', '<?php abstract class AbstractController {}');
        file_put_contents($testPath.'/ControllerTrait.php', '<?php trait ControllerTrait {}');

        $this->app['config']->set('easymodules.paths.controller', 'Presentation/Http/Controllers');
        $this->traitInstance->setMockArgument('module', 'Blog');

        $reflection = new \ReflectionClass($this->traitInstance);
        $method     = $reflection->getMethod('getModuleComponents');
        $method->setAccessible(true);

        $result = $method->invoke($this->traitInstance, 'controller');

        $this->assertContains('PostController', $result);
        $this->assertContains('UserController', $result);
        $this->assertNotContains('AbstractController', $result); // Excluded by pattern
        $this->assertNotContains('ControllerTrait', $result); // Excluded by pattern

        // Cleanup
        array_map('unlink', glob($testPath.'/*.php'));
        rmdir($testPath);
    }

    #[Test]
    public function it_gets_possible_components_with_global_callback(): void
    {
        $this->traitInstance->setMockOption('include-global', true);
        $this->traitInstance->setMockArgument('module', 'Blog'); // Required for getModuleInput

        $reflection = new \ReflectionClass($this->traitInstance);
        $method     = $reflection->getMethod('getPossibleComponents');
        $method->setAccessible(true);

        $globalCallback = fn () => ['GlobalModel1', 'GlobalModel2'];

        $result = $method->invoke($this->traitInstance, 'model', $globalCallback);

        // Should include global components when option is enabled
        $this->assertContains('GlobalModel1', $result);
        $this->assertContains('GlobalModel2', $result);
    }

    #[Test]
    public function it_gets_possible_components_without_global_callback(): void
    {
        $this->traitInstance->setMockOption('include-global', false);
        $this->traitInstance->setMockArgument('module', 'Blog'); // Required for getModuleInput

        $reflection = new \ReflectionClass($this->traitInstance);
        $method     = $reflection->getMethod('getPossibleComponents');
        $method->setAccessible(true);

        $globalCallback = fn () => ['GlobalModel1', 'GlobalModel2'];

        $result = $method->invoke($this->traitInstance, 'model', $globalCallback);

        // Should not include global components when option is disabled
        $this->assertNotContains('GlobalModel1', $result);
        $this->assertNotContains('GlobalModel2', $result);
    }

    // ================================================================================
    // Test Creation Handling
    // ================================================================================

    #[Test]
    public function it_handles_test_creation(): void
    {
        $this->traitInstance->setMockOption('test', true);
        $this->traitInstance->setMockArgument('module', 'Blog');
        $this->traitInstance->setMockArgument('name', 'PostController');

        $reflection = new \ReflectionClass($this->traitInstance);
        $method     = $reflection->getMethod('handleTestCreation');
        $method->setAccessible(true);

        $result = $method->invoke($this->traitInstance, '/some/path');

        $this->assertTrue($result); // Should return true indicating success
    }

    #[Test]
    public function it_does_not_handle_test_creation_when_option_not_set(): void
    {
        $this->traitInstance->setMockOption('test', false);
        $this->traitInstance->setMockOption('pest', false);
        $this->traitInstance->setMockOption('phpunit', false);

        $reflection = new \ReflectionClass($this->traitInstance);
        $method     = $reflection->getMethod('handleTestCreation');
        $method->setAccessible(true);

        $result = $method->invoke($this->traitInstance, '/some/path');

        $this->assertFalse($result); // Should return false when no test option is set
    }

    // ================================================================================
    // Inherited Functionality
    // ================================================================================

    #[Test]
    public function it_inherits_command_alias_manager_functionality(): void
    {
        // Test that the trait inherits CommandAliasManager methods
        $this->assertTrue(method_exists($this->traitInstance, 'configureWithAliases'));
        $this->assertTrue(method_exists($this->traitInstance, 'configureEasyModulesAliases'));
        $this->assertTrue(method_exists($this->traitInstance, 'getAlternativeCommandNames'));
    }

    #[Test]
    public function it_inherits_resolves_module_path_and_namespace_functionality(): void
    {
        // Test that the trait inherits ResolvesModulePathAndNamespaceCommand methods
        $this->assertTrue(method_exists($this->traitInstance, 'getModuleInput'));
        $this->assertTrue(method_exists($this->traitInstance, 'rootModuleNamespace'));
        $this->assertTrue(method_exists($this->traitInstance, 'moduleNamespace'));
        $this->assertTrue(method_exists($this->traitInstance, 'modulePath'));
    }
}
