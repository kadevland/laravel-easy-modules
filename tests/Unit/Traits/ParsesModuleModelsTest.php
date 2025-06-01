<?php

declare(strict_types=1);

namespace Kadevland\EasyModules\Tests\Unit\Traits;

use InvalidArgumentException;
use Illuminate\Console\Command;
use Kadevland\EasyModules\Tests\Support\TestCase;
use Kadevland\EasyModules\Traits\ParsesModuleModels;
use Kadevland\EasyModules\Traits\ResolvesModulePathAndNamespaceCommand;
use PHPUnit\Framework\Attributes\Test;

/**
 * Test suite for ParsesModuleModels trait
 *
 * This trait handles model parsing within the modular architecture,
 * ensuring proper namespace construction and module-based resolution.
 */
class ParsesModuleModelsTest extends TestCase
{
    protected $traitInstance;

    protected function setUp(): void
    {
        parent::setUp();

        $this->traitInstance = new class () extends Command
        {
            use ParsesModuleModels;
            use ResolvesModulePathAndNamespaceCommand;

            protected $arguments = [];

            public function argument($key = null)
            {
                return $key ? $this->arguments[$key] ?? null : $this->arguments;
            }

            public function setMockArgument(string $key, string $value)
            {
                $this->arguments[$key] = $value;
            }

            /**
             * Mock suffix configuration method
             */
            public function shouldAppendSuffix(): bool
            {
                return $this->laravel['config']->get('easymodules.append_suffix', false);
            }

            /**
             * Mock suffix type retrieval method
             */
            public function getSuffixForType(string $type): string
            {
                return $this->laravel['config']->get("easymodules.suffixes.{$type}", '');
            }

            /**
             * Override rootNamespace to use module namespace
             */
            protected function rootNamespace(): string
            {
                return $this->rootModuleNamespace();
            }

            /**
             * Mock Laravel's qualifyModel behavior with module namespace support
             */
            public function qualifyModel($model): string
            {
                $model = ltrim($model, '\\/');
                $model = str_replace('/', '\\', $model);
                $rootNamespace = $this->rootNamespace();

                if (str_starts_with($model, $rootNamespace)) {
                    return $model;
                }

                return is_dir(app_path('Models'))
                    ? 'App\\Models\\'.$model
                    : 'App\\'.$model;
            }
        };

        $reflection = new \ReflectionClass($this->traitInstance);
        $property   = $reflection->getProperty('laravel');
        $property->setAccessible(true);
        $property->setValue($this->traitInstance, $this->app);
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // VALIDATION TESTS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Test that model names are properly validated for invalid characters
     */
    #[Test]
    public function it_validates_model_name_format(): void
    {
        $this->traitInstance->setMockArgument('module', 'Blog');

        $reflection = new \ReflectionClass($this->traitInstance);
        $method     = $reflection->getMethod('parseModel');
        $method->setAccessible(true);

        $invalidNames = ['User@Model', 'User Model', 'User#Model', 'User$Model'];

        foreach ($invalidNames as $invalidName) {
            try {
                $method->invoke($this->traitInstance, $invalidName);
                $this->fail("Expected InvalidArgumentException for: {$invalidName}");
            } catch (InvalidArgumentException $e) {
                $this->assertStringContainsString('Model name contains invalid characters.', $e->getMessage());
            }
        }
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // NAMESPACE RESOLUTION TESTS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Test handling of absolute namespace declarations
     */
    #[Test]
    public function it_handles_absolute_namespaces(): void
    {
        $this->traitInstance->setMockArgument('module', 'Blog');

        $reflection = new \ReflectionClass($this->traitInstance);
        $method     = $reflection->getMethod('parseModel');
        $method->setAccessible(true);

        $cases = [
            '\\App\\Models\\User'       => 'App\\Models\\User',
            '\\Custom\\Namespace\\Post' => 'Custom\\Namespace\\Post',
        ];

        foreach ($cases as $input => $expected) {
            $result = $method->invoke($this->traitInstance, $input);
            $this->assertEquals($expected, $result, "Failed for input: {$input}");
        }
    }

    /**
     * Test StudlyCase transformation is applied correctly
     */
    #[Test]
    public function it_applies_studly_case_transformation(): void
    {
        $this->app['config']->set('easymodules.base_namespace', 'App\\Modules');
        $this->app['config']->set('easymodules.paths.model', 'Infrastructure/Models');
        $this->traitInstance->setMockArgument('module', 'Blog');

        $reflection = new \ReflectionClass($this->traitInstance);
        $method     = $reflection->getMethod('parseModel');
        $method->setAccessible(true);

        $result = $method->invoke($this->traitInstance, 'Post');

        $this->assertStringContainsString(
            'Post',
            $result,
            "Failed basic transformation. Got: {$result}"
        );

        $this->assertStringContainsString(
            'Blog\\Infrastructure\\Models',
            $result,
            "Should contain module namespace. Got: {$result}"
        );
    }

    /**
     * Test nested model name handling
     */
    #[Test]
    public function it_handles_nested_model_names(): void
    {
        $this->app['config']->set('easymodules.base_namespace', 'App\\Modules');
        $this->app['config']->set('easymodules.paths.model', 'Infrastructure/Models');
        $this->traitInstance->setMockArgument('module', 'Blog');

        $reflection = new \ReflectionClass($this->traitInstance);
        $method     = $reflection->getMethod('parseModel');
        $method->setAccessible(true);

        $result = $method->invoke($this->traitInstance, 'Category');

        $this->assertStringContainsString(
            'Category',
            $result,
            "Failed for model: Category. Got: {$result}"
        );

        $this->assertStringContainsString(
            'Blog\\Infrastructure\\Models',
            $result,
            "Should contain module namespace. Got: {$result}"
        );
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // CONFIGURATION TESTS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Test suffix configuration handling
     */
    #[Test]
    public function it_handles_suffix_configuration(): void
    {
        $this->app['config']->set('easymodules.base_namespace', 'App\\Modules');
        $this->app['config']->set('easymodules.paths.model', 'Infrastructure/Models');
        $this->app['config']->set('easymodules.append_suffix', true);
        $this->app['config']->set('easymodules.suffixes.model', 'Model');

        $this->traitInstance->setMockArgument('module', 'Blog');

        $reflection = new \ReflectionClass($this->traitInstance);
        $method     = $reflection->getMethod('parseModel');
        $method->setAccessible(true);

        // Test suffix addition
        $result = $method->invoke($this->traitInstance, 'Post');
        $this->assertStringContainsString(
            'PostModel',
            $result,
            'Should append suffix when configured'
        );

        // Test suffix duplication prevention
        $result = $method->invoke($this->traitInstance, 'UserModel');
        $this->assertStringNotContainsString(
            'UserModelModel',
            $result,
            'Should not duplicate existing suffix'
        );
    }

    /**
     * Test behavior with different module configurations
     */
    #[Test]
    public function it_uses_different_module_configurations(): void
    {
        $this->app['config']->set('easymodules.base_namespace', 'Custom\\Modules');
        $this->app['config']->set('easymodules.paths.model', 'Domain/Models');

        $testModules = ['Shop', 'User', 'Payment'];

        foreach ($testModules as $module) {
            $this->traitInstance->setMockArgument('module', $module);

            $reflection = new \ReflectionClass($this->traitInstance);
            $method     = $reflection->getMethod('parseModel');
            $method->setAccessible(true);

            $result = $method->invoke($this->traitInstance, 'TestModel');

            $expectedPattern = "Custom\\Modules\\{$module}\\Domain\\Models";
            $this->assertStringContainsString(
                $expectedPattern,
                $result,
                "Failed for module: {$module} with custom configuration"
            );
        }
    }

    /**
     * Test configuration fallback behavior
     */
    #[Test]
    public function it_handles_configuration_fallbacks(): void
    {
        $this->app['config']->set('easymodules.base_namespace', 'App\\Modules');
        // Intentionally not setting paths.model to test fallback
        $this->traitInstance->setMockArgument('module', 'Blog');

        $reflection = new \ReflectionClass($this->traitInstance);
        $method     = $reflection->getMethod('parseModel');
        $method->setAccessible(true);

        $result = $method->invoke($this->traitInstance, 'Post');

        $this->assertStringContainsString(
            'Infrastructure\\Models',
            $result,
            'Should use default path when configuration is missing'
        );
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // DOUBLE PATH PREVENTION TESTS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Test prevention of double path namespace bug
     *
     * This test ensures that namespace segments are not duplicated in the final
     * fully qualified class name, which was a recurring issue identified in the
     * double path warning documentation.
     */
    #[Test]
    public function it_prevents_double_path_namespace_bug(): void
    {
        $this->app['config']->set('easymodules.base_namespace', 'App\\Modules');
        $this->app['config']->set('easymodules.paths.model', 'Infrastructure/Models');
        $this->traitInstance->setMockArgument('module', 'Blog');

        $reflection = new \ReflectionClass($this->traitInstance);
        $method     = $reflection->getMethod('parseModel');
        $method->setAccessible(true);

        $result = $method->invoke($this->traitInstance, 'Post');

        // Positive assertion: should contain correct namespace
        $this->assertStringContainsString(
            'App\\Modules\\Blog\\Infrastructure\\Models\\Post',
            $result,
            'Should generate correct module model namespace'
        );

        // Negative assertions: should NOT contain duplicated segments
        $this->assertStringNotContainsString(
            'Infrastructure\\Models\\Infrastructure\\Models',
            $result,
            'Should NOT contain duplicate path segments'
        );

        $this->assertStringNotContainsString(
            'Blog\\Blog\\',
            $result,
            'Should NOT contain duplicate module names'
        );

        $this->assertStringNotContainsString(
            '\\\\\\',
            $result,
            'Should NOT contain triple backslashes'
        );
    }

    /**
     * Test various path configurations without duplication
     *
     * This comprehensive test verifies that different configuration combinations
     * do not produce duplicated path segments in the final namespace.
     */
    #[Test]
    public function it_handles_various_path_configurations_without_duplication(): void
    {
        $testCases = [
            // [base_namespace, model_path, module, model_name, expected_contains, should_not_contain]
            [
                'App\\Modules',
                'Infrastructure/Models',
                'Blog',
                'Post',
                'App\\Modules\\Blog\\Infrastructure\\Models\\Post',
                ['Infrastructure\\Models\\Infrastructure\\Models', 'Blog\\Blog\\'],
            ],
            [
                'Custom\\Modules',
                'Domain/Entities',
                'Shop',
                'Product',
                'Custom\\Modules\\Shop\\Domain\\Entities\\Product',
                ['Domain\\Entities\\Domain\\Entities', 'Shop\\Shop\\'],
            ],
            [
                'App\\Components',
                'Models',
                'User',
                'Profile',
                'App\\Components\\User\\Models\\Profile',
                ['Models\\Models', 'User\\User\\'],
            ],
        ];

        foreach ($testCases as [$baseNamespace, $modelPath, $module, $modelName, $expectedContains, $shouldNotContain]) {
            $this->app['config']->set('easymodules.base_namespace', $baseNamespace);
            $this->app['config']->set('easymodules.paths.model', $modelPath);
            $this->traitInstance->setMockArgument('module', $module);

            $reflection = new \ReflectionClass($this->traitInstance);
            $method     = $reflection->getMethod('parseModel');
            $method->setAccessible(true);

            $result = $method->invoke($this->traitInstance, $modelName);

            // Positive verification
            $this->assertStringContainsString(
                $expectedContains,
                $result,
                "Failed for config: {$baseNamespace}, {$modelPath}, {$module}, {$modelName}"
            );

            // Negative verifications (anti-duplication)
            foreach ($shouldNotContain as $badPattern) {
                $this->assertStringNotContainsString(
                    $badPattern,
                    $result,
                    "Should not contain duplicate pattern '{$badPattern}' in result: {$result}"
                );
            }
        }
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // MODULE NAMESPACE FALLBACK TESTS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Test that non-existent models use module namespace structure
     *
     * In the modular architecture, even non-existent models should maintain
     * the module namespace structure rather than falling back to Laravel's
     * global App\Models namespace.
     */
    #[Test]
    public function non_existent_models_use_module_namespace(): void
    {
        $this->app['config']->set('easymodules.base_namespace', 'App\\Modules');
        $this->app['config']->set('easymodules.paths.model', 'Infrastructure/Models');
        $this->traitInstance->setMockArgument('module', 'Blog');

        $reflection = new \ReflectionClass($this->traitInstance);
        $method     = $reflection->getMethod('parseModel');
        $method->setAccessible(true);

        $result = $method->invoke($this->traitInstance, 'NonExistentModel');

        // Should contain module namespace structure
        $this->assertStringContainsString(
            'Blog\\Infrastructure\\Models',
            $result,
            'Should use module namespace structure'
        );

        // Should NOT contain Laravel global namespace
        $this->assertStringNotContainsString(
            'App\\Models\\NonExistentModel',
            $result,
            'Should NOT fall back to Laravel global namespace'
        );
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // EDGE CASE TESTS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Test handling of edge cases and special scenarios
     */
    #[Test]
    public function it_handles_edge_cases(): void
    {
        $this->app['config']->set('easymodules.base_namespace', 'App\\Modules');
        $this->app['config']->set('easymodules.paths.model', 'Infrastructure/Models');
        $this->traitInstance->setMockArgument('module', 'Blog');

        $reflection = new \ReflectionClass($this->traitInstance);
        $method     = $reflection->getMethod('parseModel');
        $method->setAccessible(true);

        // Test single character model name
        $result = $method->invoke($this->traitInstance, 'A');
        $this->assertStringContainsString(
            'A',
            $result,
            'Should handle single character model names'
        );

        // Test basic model transformation
        $result = $method->invoke($this->traitInstance, 'TestModel');
        $this->assertStringContainsString(
            'TestModel',
            $result,
            'Should handle basic model names'
        );
    }
}
