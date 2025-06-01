<?php

declare(strict_types=1);

namespace Kadevland\EasyModules\Tests\Unit\Commands\Make\Laravel;

use Kadevland\EasyModules\Tests\Support\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Test suite for TestMakeCommand
 *
 * This command extends Laravel's base TestMakeCommand to generate
 * test classes within the modular structure, supporting both
 * feature tests and unit tests.
 */
class TestMakeCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->setComponentPath('featuretest', 'Tests/Feature');
        $this->setComponentPath('unittest', 'Tests/Unit');

        // Set app environment to testing to disable prompts
        $this->app['config']->set('app.env', 'testing');
    }

    protected function tearDown(): void
    {
        // Enhanced cleanup - remove all test files
        $testPaths = [
            'Blog/Tests/Feature',
            'Blog/Tests/Unit',
            'Shop/Tests/Feature',
            'Shop/Tests/Unit',
            'Test/Tests/Feature',
            'Test/Tests/Unit',
            'Custom/Testing/Unit',
        ];

        foreach ($testPaths as $path) {
            $fullPath = $this->testBasePath($path);
            if ($this->files->isDirectory($fullPath)) {
                $this->files->deleteDirectory($fullPath, true);
            }
        }

        parent::tearDown();
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // NIVEAU 5: BASIC TEST GENERATION TESTS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Test basic feature test file generation
     */
    #[Test]
    public function it_can_generate_basic_feature_test_file(): void
    {
        $this->runEasyModulesCommand('make-test', 'Blog', 'PostTest');

        $this->assertModuleComponentExists('Blog', 'Tests/Feature', 'PostTest', [
            'use Tests\TestCase;',
            'class PostTest extends TestCase',
            'public function test_example(): void',
        ]);
    }

    /**
     * Test unit test file generation
     */
    #[Test]
    public function it_can_generate_unit_test_file(): void
    {
        $this->runEasyModulesCommand('make-test', 'Blog', 'UserTest', ['--unit' => true]);

        $this->assertModuleComponentExists('Blog', 'Tests/Unit', 'UserTest', [
            'use PHPUnit\Framework\TestCase;',
            'class UserTest extends TestCase',
            'public function test_example(): void',
        ]);
    }

    /**
     * Test generation with different naming patterns
     */
    #[Test]
    public function it_can_generate_tests_with_different_names(): void
    {
        $testNames = [
            'UserTest',
            'PostTest',
            'AuthTest',
            'ApiTest',
        ];

        foreach ($testNames as $testName) {
            $this->runEasyModulesCommand('make-test', 'Blog', $testName);

            $this->assertFilenameExists("Blog/Tests/Feature/{$testName}.php");
            $this->assertFileContains([
                'namespace App\\Modules\\Blog\\Tests\\Feature;',
                'use Tests\\TestCase;',
                "class {$testName} extends TestCase",
            ], "Blog/Tests/Feature/{$testName}.php");
        }
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // NIVEAU 4: NAMESPACE CORRECTNESS TESTS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Test feature test namespace generation is correct
     */
    #[Test]
    public function it_generates_correct_feature_test_namespace(): void
    {
        $this->runEasyModulesCommand('make-test', 'Blog', 'NamespaceTest');

        // Feature test should have correct namespace
        $this->assertFileContains([
            'namespace App\\Modules\\Blog\\Tests\\Feature;',
        ], 'Blog/Tests/Feature/NamespaceTest.php');

        // Should NOT contain duplicated namespace segments
        $this->assertFileNotContains([
            'namespace App\\Modules\\Blog\\Tests\\Feature\\Tests\\Feature;',
            'namespace App\\Modules\\Blog\\Blog\\Tests\\Feature;',
        ], 'Blog/Tests/Feature/NamespaceTest.php');
    }

    /**
     * Test unit test namespace generation is correct
     */
    #[Test]
    public function it_generates_correct_unit_test_namespace(): void
    {
        $this->runEasyModulesCommand('make-test', 'Blog', 'UnitNamespaceTest', ['--unit' => true]);

        // Unit test should have correct namespace
        $this->assertFileContains([
            'namespace App\\Modules\\Blog\\Tests\\Unit;',
        ], 'Blog/Tests/Unit/UnitNamespaceTest.php');

        // Should NOT contain duplicated namespace segments
        $this->assertFileNotContains([
            'namespace App\\Modules\\Blog\\Tests\\Unit\\Tests\\Unit;',
            'namespace App\\Modules\\Blog\\Blog\\Tests\\Unit;',
        ], 'Blog/Tests/Unit/UnitNamespaceTest.php');
    }

    /**
     * Test structure is properly generated
     */
    #[Test]
    public function it_generates_correct_test_structure(): void
    {
        $this->runEasyModulesCommand('make-test', 'Blog', 'StructureTest');

        $this->assertFileContains([
            'use Tests\\TestCase;',
            'class StructureTest extends TestCase',
            'public function test_example(): void',
        ], 'Blog/Tests/Feature/StructureTest.php');
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // NIVEAU 3: DOUBLE PATH PREVENTION TESTS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Test prevention of double path and namespace duplication
     *
     * This test ensures that neither file paths nor namespaces are duplicated
     * when generating tests within the module structure.
     */
    #[Test]
    public function it_prevents_double_path_and_namespace_duplication(): void
    {
        $this->runEasyModulesCommand('make-test', 'Blog', 'PathTest');

        // ✅ File path verification
        $this->assertFilenameExists('Blog/Tests/Feature/PathTest.php');
        $this->assertFilenameNotExists('Blog/Tests/Feature/Tests/Feature/PathTest.php');

        // ✅ Namespace duplication prevention
        $this->assertFileNotContains([
            'App\\Modules\\Blog\\Tests\\Feature\\Tests\\Feature',
            'App\\Modules\\Blog\\Blog\\Tests\\Feature',
            'Tests\\Feature\\Tests\\Feature',
        ], 'Blog/Tests/Feature/PathTest.php');

        // Test unit tests as well
        $this->runEasyModulesCommand('make-test', 'Blog', 'UnitPathTest', ['--unit' => true]);

        $this->assertFilenameExists('Blog/Tests/Unit/UnitPathTest.php');
        $this->assertFilenameNotExists('Blog/Tests/Unit/Tests/Unit/UnitPathTest.php');

        $this->assertFileNotContains([
            'App\\Modules\\Blog\\Tests\\Unit\\Tests\\Unit',
            'App\\Modules\\Blog\\Blog\\Tests\\Unit',
            'Tests\\Unit\\Tests\\Unit',
        ], 'Blog/Tests/Unit/UnitPathTest.php');
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // NIVEAU 2: CUSTOM CONFIGURATION TESTS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Test generation with nested names
     */
    #[Test]
    public function it_can_generate_nested_tests(): void
    {
        $this->runEasyModulesCommand('make-test', 'Blog', 'Auth/LoginTest');

        // Should create nested directory structure
        $this->assertFilenameExists('Blog/Tests/Feature/Auth/LoginTest.php');

        $this->assertFileContains([
            'namespace App\\Modules\\Blog\\Tests\\Feature\\Auth;',
            'class LoginTest extends TestCase',
        ], 'Blog/Tests/Feature/Auth/LoginTest.php');
    }

    /**
     * Test deeply nested test generation
     */
    #[Test]
    public function it_handles_deeply_nested_tests(): void
    {
        $this->runEasyModulesCommand('make-test', 'Blog', 'Api/V1/UserTest');

        $this->assertFilenameExists('Blog/Tests/Feature/Api/V1/UserTest.php');

        $this->assertFileContains([
            'namespace App\\Modules\\Blog\\Tests\\Feature\\Api\\V1;',
            'class UserTest extends TestCase',
        ], 'Blog/Tests/Feature/Api/V1/UserTest.php');

        // Should not contain path duplication in deeply nested structure
        $this->assertFileNotContains([
            'namespace App\\Modules\\Blog\\Tests\\Feature\\Api\\V1\\Tests\\Feature\\Api\\V1;',
        ], 'Blog/Tests/Feature/Api/V1/UserTest.php');
    }

    /**
     * Test generation with custom module configuration
     */
    #[Test]
    public function it_handles_custom_module_configurations(): void
    {
        // Test with custom base namespace and paths
        $this->app['config']->set('easymodules.base_namespace', 'Custom\\Modules');
        $this->app['config']->set('easymodules.paths.featuretest', 'Tests/Feature');
        $this->app['config']->set('easymodules.paths.unittest', 'Tests/Unit');

        $this->runEasyModulesCommand('make-test', 'Shop', 'ProductTest');

        // Check custom paths are used
        $this->assertFilenameExists('Shop/Tests/Feature/ProductTest.php');

        // Check custom namespaces
        $this->assertFileContains([
            'namespace Custom\\Modules\\Shop\\Tests\\Feature;',
            'class ProductTest extends TestCase',
        ], 'Shop/Tests/Feature/ProductTest.php');

        // Should NOT contain double namespace with custom config
        $this->assertFileNotContains([
            'namespace Custom\\Modules\\Shop\\Tests\\Feature\\Tests\\Feature;',
        ], 'Shop/Tests/Feature/ProductTest.php');
    }

    /**
     * Test generation with different module structures
     */
    #[Test]
    public function it_handles_different_module_structures(): void
    {
        $testConfigs = [
            ['namespace' => 'App\\Components', 'featurePath' => 'Tests/Feature', 'unitPath' => 'Tests/Unit'],
            ['namespace' => 'Modules', 'featurePath' => 'Testing/Feature', 'unitPath' => 'Testing/Unit'],
            ['namespace' => 'Custom\\App\\Modules', 'featurePath' => 'Tests/Feature', 'unitPath' => 'Tests/Unit'],
        ];

        foreach ($testConfigs as $index => $config) {
            $this->app['config']->set('easymodules.base_namespace', $config['namespace']);
            $this->app['config']->set('easymodules.paths.featuretest', $config['featurePath']);
            $this->app['config']->set('easymodules.paths.unittest', $config['unitPath']);

            $testName = "Test{$index}Test";

            $this->runEasyModulesCommand('make-test', 'Test', $testName);

            $expectedTestPath = "Test/{$config['featurePath']}/{$testName}.php";
            $this->assertFilenameExists($expectedTestPath);

            $expectedTestNamespace = str_replace('/', '\\', "{$config['namespace']}\\Test\\{$config['featurePath']}");
            $this->assertFileContains([
                "namespace {$expectedTestNamespace};",
                "class {$testName}",
            ], $expectedTestPath);

            // Should not contain duplicated path segments
            $pathSegments = explode('/', $config['featurePath']);

            $duplicatedPatterns = [];
            foreach ($pathSegments as $segment) {
                $duplicatedPatterns[] = "{$expectedTestNamespace}\\{$segment}\\{$segment}";
            }

            $this->assertFileNotContains($duplicatedPatterns, $expectedTestPath);
        }
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // NIVEAU 1: EDGE CASE & INTEGRATION TESTS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Test generation with complex names
     */
    #[Test]
    public function it_handles_complex_test_names(): void
    {
        $complexCases = [
            'UserRegistrationTest',
            'BlogPostCreationTest',
            'ApiAuthenticationTest',
            'PaymentProcessingTest',
        ];

        foreach ($complexCases as $testName) {
            $this->runEasyModulesCommand('make-test', 'Test', $testName);

            $this->assertFilenameExists("Test/Tests/Feature/{$testName}.php");

            $this->assertFileContains([
                "class {$testName} extends TestCase",
                'namespace App\\Modules\\Test\\Tests\\Feature;',
            ], "Test/Tests/Feature/{$testName}.php");
        }
    }

    /**
     * Test generation edge cases
     */
    #[Test]
    public function it_handles_edge_cases_gracefully(): void
    {
        // Test with single character names
        $this->runEasyModulesCommand('make-test', 'Blog', 'ATest');

        $this->assertFilenameExists('Blog/Tests/Feature/ATest.php');

        $this->assertFileContains([
            'class ATest extends TestCase',
        ], 'Blog/Tests/Feature/ATest.php');

        // Test with numbers in names
        $this->runEasyModulesCommand('make-test', 'Blog', 'Api2Test');

        $this->assertFilenameExists('Blog/Tests/Feature/Api2Test.php');

        $this->assertFileContains([
            'class Api2Test extends TestCase',
        ], 'Blog/Tests/Feature/Api2Test.php');
    }

    /**
     * Test works with various module structures
     */
    #[Test]
    public function it_works_with_various_module_structures(): void
    {
        // Test multiple modules
        $modules = ['Blog', 'Shop', 'Admin'];

        foreach ($modules as $module) {
            $this->runEasyModulesCommand('make-test', $module, 'TestClass');

            $this->assertFilenameExists("{$module}/Tests/Feature/TestClass.php");

            $this->assertFileContains([
                "namespace App\\Modules\\{$module}\\Tests\\Feature;",
                'class TestClass extends TestCase',
            ], "{$module}/Tests/Feature/TestClass.php");
        }
    }

    /**
     * Test multiple tests in same module
     */
    #[Test]
    public function it_handles_multiple_tests_in_same_module(): void
    {
        $tests = [
            ['name' => 'UserTest', 'type' => 'feature'],
            ['name' => 'PostTest', 'type' => 'feature'],
            ['name' => 'UnitUserTest', 'type' => 'unit'],
            ['name' => 'Auth/LoginTest', 'type' => 'feature'],
        ];

        foreach ($tests as $test) {
            $options = $test['type'] === 'unit' ? ['--unit' => true] : [];
            $this->runEasyModulesCommand('make-test', 'Blog', $test['name'], $options);

            $testType     = $test['type'] === 'unit' ? 'Unit' : 'Feature';
            $expectedFile = "Blog/Tests/{$testType}/{$test['name']}.php";
            $this->assertFilenameExists($expectedFile);
        }

        // Verify all files exist and have correct content
        foreach ($tests as $test) {
            $testType     = $test['type'] === 'unit' ? 'Unit' : 'Feature';
            $expectedFile = "Blog/Tests/{$testType}/{$test['name']}.php";
            $className    = basename($test['name']);
            $this->assertFileContains([
                "class {$className} extends TestCase",
            ], $expectedFile);
        }
    }

    /**
     * Test unit option behavior
     */
    #[Test]
    public function it_handles_unit_option_correctly(): void
    {
        // Feature test should use Tests\TestCase
        $this->runEasyModulesCommand('make-test', 'Blog', 'FeatureTest');

        $this->assertFileContains([
            'use Tests\\TestCase;',
            'class FeatureTest extends TestCase',
        ], 'Blog/Tests/Feature/FeatureTest.php');

        $this->assertFileNotContains([
            'use PHPUnit\\Framework\\TestCase;',
        ], 'Blog/Tests/Feature/FeatureTest.php');

        // Unit test should use PHPUnit\Framework\TestCase
        $this->runEasyModulesCommand('make-test', 'Blog', 'UnitTest', ['--unit' => true]);

        $this->assertFileContains([
            'use PHPUnit\\Framework\\TestCase;',
            'class UnitTest extends TestCase',
        ], 'Blog/Tests/Unit/UnitTest.php');

        $this->assertFileNotContains([
            'use Tests\\TestCase;',
        ], 'Blog/Tests/Unit/UnitTest.php');
    }

    /**
     * Test with nested structure and unit option
     */
    #[Test]
    public function it_can_generate_nested_unit_tests(): void
    {
        $this->runEasyModulesCommand('make-test', 'Blog', 'Services/UserServiceTest', ['--unit' => true]);

        $this->assertFilenameExists('Blog/Tests/Unit/Services/UserServiceTest.php');

        $this->assertFileContains([
            'namespace App\\Modules\\Blog\\Tests\\Unit\\Services;',
            'use PHPUnit\\Framework\\TestCase;',
            'class UserServiceTest extends TestCase',
            'public function test_example(): void',
        ], 'Blog/Tests/Unit/Services/UserServiceTest.php');

        // Should NOT contain feature test imports
        $this->assertFileNotContains([
            'use Tests\\TestCase;',
        ], 'Blog/Tests/Unit/Services/UserServiceTest.php');
    }

    /**
     * Test that generated tests have proper structure
     */
    #[Test]
    public function it_generates_proper_test_structure(): void
    {
        $this->runEasyModulesCommand('make-test', 'Blog', 'StructureTestCase');

        $this->assertFileContains([
            'public function test_example(): void',
            '$response->assertStatus(200);',
        ], 'Blog/Tests/Feature/StructureTestCase.php');
    }

    /**
     * Test pest option support if available
     */
    #[Test]
    public function it_handles_pest_option_when_available(): void
    {
        // Test with --pest option (may not be available in all Laravel versions)
        $this->runEasyModulesCommand('make-test', 'Blog', 'PestTest', ['--pest' => true]);

        $this->assertFilenameExists('Blog/Tests/Feature/PestTest.php');

        // Pest tests don't have namespaces, they use functions
        $pestContent = $this->files->get($this->testBasePath('Blog/Tests/Feature/PestTest.php'));

        // Check if it's a Pest test (function-based) or regular test (class-based)
        if (str_contains($pestContent, 'test(')) {
            // It's a Pest test - no namespace expected
            $this->assertFileContains([
                "test('example', function ()",
            ], 'Blog/Tests/Feature/PestTest.php');
        } else {
            // It's a regular test - should have namespace
            $this->assertFileContains([
                'namespace App\\Modules\\Blog\\Tests\\Feature;',
            ], 'Blog/Tests/Feature/PestTest.php');
        }
    }
}
