<?php

declare(strict_types=1);

namespace Kadevland\EasyModules\Tests\Unit\Commands\Make\Laravel;

use Kadevland\EasyModules\Tests\Support\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Test suite for RuleMakeCommand
 *
 * This command extends Laravel's base RuleMakeCommand to generate
 * validation rule classes within the modular structure.
 */
class RuleMakeCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->setComponentPath('rule', 'Infrastructure/Rules');

        // Set app environment to testing to disable prompts
        $this->app['config']->set('app.env', 'testing');
    }

    protected function tearDown(): void
    {
        // Enhanced cleanup - remove all test files
        $testPaths = [
            'Blog/Infrastructure/Rules',
            'Shop/Infrastructure/Rules',
            'Test/Infrastructure/Rules',
            'Custom/Domain/Rules',
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
    // NIVEAU 5: BASIC RULE GENERATION TESTS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Test basic rule file generation
     */
    #[Test]
    public function it_can_generate_basic_rule_file(): void
    {
        $this->runEasyModulesCommand('make-rule', 'Blog', 'ValidSlug');

        $this->assertModuleComponentExists('Blog', 'Infrastructure/Rules', 'ValidSlug', [
            'use Illuminate\Contracts\Validation\ValidationRule;',
            'class ValidSlug implements ValidationRule',
            'public function validate(string $attribute, mixed $value, Closure $fail)',
            'use Closure;',
        ]);
    }

    /**
     * Test rule generation with different naming patterns
     */
    #[Test]
    public function it_can_generate_rules_with_different_names(): void
    {
        $ruleNames = [
            'ValidSlug',
            'UniqueEmail',
            'StrongPassword',
            'ValidPhoneNumber',
        ];

        foreach ($ruleNames as $ruleName) {
            $this->runEasyModulesCommand('make-rule', 'Blog', $ruleName);

            $this->assertFilenameExists("Blog/Infrastructure/Rules/{$ruleName}.php");
            $this->assertFileContains([
                'namespace App\\Modules\\Blog\\Infrastructure\\Rules;',
                'use Illuminate\\Contracts\\Validation\\ValidationRule;',
                "class {$ruleName} implements ValidationRule",
            ], "Blog/Infrastructure/Rules/{$ruleName}.php");
        }
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // NIVEAU 4: NAMESPACE CORRECTNESS TESTS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Test rule namespace generation is correct
     */
    #[Test]
    public function it_generates_correct_rule_namespace(): void
    {
        $this->runEasyModulesCommand('make-rule', 'Blog', 'NamespaceTest');

        // Rule should have correct namespace
        $this->assertFileContains([
            'namespace App\\Modules\\Blog\\Infrastructure\\Rules;',
        ], 'Blog/Infrastructure/Rules/NamespaceTest.php');

        // Should NOT contain duplicated namespace segments
        $this->assertFileNotContains([
            'namespace App\\Modules\\Blog\\Infrastructure\\Rules\\Infrastructure\\Rules;',
            'namespace App\\Modules\\Blog\\Blog\\Infrastructure\\Rules;',
        ], 'Blog/Infrastructure/Rules/NamespaceTest.php');
    }

    /**
     * Test rule structure is properly generated
     */
    #[Test]
    public function it_generates_correct_rule_structure(): void
    {
        $this->runEasyModulesCommand('make-rule', 'Blog', 'StructureTest');

        $this->assertFileContains([
            'use Illuminate\\Contracts\\Validation\\ValidationRule;',
            'class StructureTest implements ValidationRule',
            'public function validate(string $attribute, mixed $value, Closure $fail)',
        ], 'Blog/Infrastructure/Rules/StructureTest.php');
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // NIVEAU 3: DOUBLE PATH PREVENTION TESTS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Test prevention of double path and namespace duplication
     *
     * This test ensures that neither file paths nor namespaces are duplicated
     * when generating rules within the module structure.
     */
    #[Test]
    public function it_prevents_double_path_and_namespace_duplication(): void
    {
        $this->runEasyModulesCommand('make-rule', 'Blog', 'PathTest');

        // ✅ File path verification
        $this->assertFilenameExists('Blog/Infrastructure/Rules/PathTest.php');
        $this->assertFilenameNotExists('Blog/Infrastructure/Rules/Infrastructure/Rules/PathTest.php');

        // ✅ Namespace duplication prevention
        $this->assertFileNotContains([
            'App\\Modules\\Blog\\Infrastructure\\Rules\\Infrastructure\\Rules',
            'App\\Modules\\Blog\\Blog\\Infrastructure\\Rules',
            'Infrastructure\\Rules\\Infrastructure\\Rules',
        ], 'Blog/Infrastructure/Rules/PathTest.php');
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // NIVEAU 2: CUSTOM CONFIGURATION TESTS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Test rule generation with nested names
     */
    #[Test]
    public function it_can_generate_nested_rules(): void
    {
        $this->runEasyModulesCommand('make-rule', 'Blog', 'Validation/UniqueEmail');

        // Should create nested directory structure
        $this->assertFilenameExists('Blog/Infrastructure/Rules/Validation/UniqueEmail.php');

        $this->assertFileContains([
            'namespace App\\Modules\\Blog\\Infrastructure\\Rules\\Validation;',
            'class UniqueEmail implements ValidationRule',
        ], 'Blog/Infrastructure/Rules/Validation/UniqueEmail.php');
    }

    /**
     * Test deeply nested rule generation
     */
    #[Test]
    public function it_handles_deeply_nested_rules(): void
    {
        $this->runEasyModulesCommand('make-rule', 'Blog', 'Auth/Password/StrongPassword');

        $this->assertFilenameExists('Blog/Infrastructure/Rules/Auth/Password/StrongPassword.php');

        $this->assertFileContains([
            'namespace App\\Modules\\Blog\\Infrastructure\\Rules\\Auth\\Password;',
            'class StrongPassword implements ValidationRule',
        ], 'Blog/Infrastructure/Rules/Auth/Password/StrongPassword.php');

        // Should not contain path duplication in deeply nested structure
        $this->assertFileNotContains([
            'namespace App\\Modules\\Blog\\Infrastructure\\Rules\\Auth\\Password\\Infrastructure\\Rules\\Auth\\Password;',
        ], 'Blog/Infrastructure/Rules/Auth/Password/StrongPassword.php');
    }

    /**
     * Test rule generation with custom module configuration
     */
    #[Test]
    public function it_handles_custom_module_configurations(): void
    {
        // Test with custom base namespace and paths
        $this->app['config']->set('easymodules.base_namespace', 'Custom\\Modules');
        $this->app['config']->set('easymodules.paths.rule', 'Domain/Rules');

        $this->runEasyModulesCommand('make-rule', 'Shop', 'ValidPrice');

        // Check custom paths are used
        $this->assertFilenameExists('Shop/Domain/Rules/ValidPrice.php');

        // Check custom namespaces
        $this->assertFileContains([
            'namespace Custom\\Modules\\Shop\\Domain\\Rules;',
            'class ValidPrice implements ValidationRule',
        ], 'Shop/Domain/Rules/ValidPrice.php');

        // Should NOT contain double namespace with custom config
        $this->assertFileNotContains([
            'namespace Custom\\Modules\\Shop\\Domain\\Rules\\Domain\\Rules;',
        ], 'Shop/Domain/Rules/ValidPrice.php');
    }

    /**
     * Test rule generation with different module structures
     */
    #[Test]
    public function it_handles_different_module_structures(): void
    {
        $testConfigs = [
            ['namespace' => 'App\\Components', 'rulePath' => 'Rules'],
            ['namespace' => 'Modules', 'rulePath' => 'Validation/Rules'],
            ['namespace' => 'Custom\\App\\Modules', 'rulePath' => 'Infrastructure/Rules'],
        ];

        foreach ($testConfigs as $index => $config) {
            $this->app['config']->set('easymodules.base_namespace', $config['namespace']);
            $this->app['config']->set('easymodules.paths.rule', $config['rulePath']);

            $ruleName = "Test{$index}Rule";

            $this->runEasyModulesCommand('make-rule', 'Test', $ruleName);

            $expectedRulePath = "Test/{$config['rulePath']}/{$ruleName}.php";
            $this->assertFilenameExists($expectedRulePath);

            $expectedRuleNamespace = str_replace('/', '\\', "{$config['namespace']}\\Test\\{$config['rulePath']}");
            $this->assertFileContains([
                "namespace {$expectedRuleNamespace};",
                "class {$ruleName}",
            ], $expectedRulePath);

            // Should not contain duplicated path segments
            $pathSegments = explode('/', $config['rulePath']);

            $duplicatedPatterns = [];
            foreach ($pathSegments as $segment) {
                $duplicatedPatterns[] = "{$expectedRuleNamespace}\\{$segment}\\{$segment}";
            }

            $this->assertFileNotContains($duplicatedPatterns, $expectedRulePath);
        }
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // NIVEAU 1: EDGE CASE & INTEGRATION TESTS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Test rule generation with complex names
     */
    #[Test]
    public function it_handles_complex_rule_names(): void
    {
        $complexCases = [
            'ValidEmailAddress',
            'StrongPasswordValidator',
            'UniqueUsernameRule',
            'PhoneNumberFormat',
        ];

        foreach ($complexCases as $ruleName) {
            $this->runEasyModulesCommand('make-rule', 'Test', $ruleName);

            $this->assertFilenameExists("Test/Infrastructure/Rules/{$ruleName}.php");

            $this->assertFileContains([
                "class {$ruleName} implements ValidationRule",
                'namespace App\\Modules\\Test\\Infrastructure\\Rules;',
            ], "Test/Infrastructure/Rules/{$ruleName}.php");
        }
    }

    /**
     * Test rule generation edge cases
     */
    #[Test]
    public function it_handles_edge_cases_gracefully(): void
    {
        // Test with single character names
        $this->runEasyModulesCommand('make-rule', 'Blog', 'A');

        $this->assertFilenameExists('Blog/Infrastructure/Rules/A.php');

        $this->assertFileContains([
            'class A implements ValidationRule',
        ], 'Blog/Infrastructure/Rules/A.php');

        // Test with numbers in names
        $this->runEasyModulesCommand('make-rule', 'Blog', 'MinLength8');

        $this->assertFilenameExists('Blog/Infrastructure/Rules/MinLength8.php');

        $this->assertFileContains([
            'class MinLength8 implements ValidationRule',
        ], 'Blog/Infrastructure/Rules/MinLength8.php');
    }

    /**
     * Test rule works with various module structures
     */
    #[Test]
    public function it_works_with_various_module_structures(): void
    {
        // Test multiple modules
        $modules = ['Blog', 'Shop', 'Admin'];

        foreach ($modules as $module) {
            $this->runEasyModulesCommand('make-rule', $module, 'TestRule');

            $this->assertFilenameExists("{$module}/Infrastructure/Rules/TestRule.php");

            $this->assertFileContains([
                "namespace App\\Modules\\{$module}\\Infrastructure\\Rules;",
                'class TestRule implements ValidationRule',
            ], "{$module}/Infrastructure/Rules/TestRule.php");
        }
    }

    /**
     * Test multiple rules in same module
     */
    #[Test]
    public function it_handles_multiple_rules_in_same_module(): void
    {
        $rules = [
            'ValidSlug',
            'UniqueEmail',
            'StrongPassword',
            'Validation/MinLength',
        ];

        foreach ($rules as $rulePath) {
            $this->runEasyModulesCommand('make-rule', 'Blog', $rulePath);

            $expectedFile = "Blog/Infrastructure/Rules/{$rulePath}.php";
            $this->assertFilenameExists($expectedFile);
        }

        // Verify all files exist and have correct content
        foreach ($rules as $rulePath) {
            $expectedFile = "Blog/Infrastructure/Rules/{$rulePath}.php";
            $className    = basename($rulePath);
            $this->assertFileContains([
                "class {$className} implements ValidationRule",
            ], $expectedFile);
        }
    }

    /**
     * Test suffix configuration behavior
     */
    #[Test]
    public function it_handles_suffix_configuration(): void
    {
        // Enable suffix appending
        $this->app['config']->set('easymodules.append_suffix', true);
        $this->app['config']->set('easymodules.suffixes.rule', 'Rule');

        // Test suffix addition when missing
        $this->runEasyModulesCommand('make-rule', 'Blog', 'ValidSlug');

        $this->assertFilenameExists('Blog/Infrastructure/Rules/ValidSlugRule.php');
        $this->assertFileContains([
            'class ValidSlugRule implements ValidationRule',
        ], 'Blog/Infrastructure/Rules/ValidSlugRule.php');

        // Test suffix not duplicated when already present
        $this->runEasyModulesCommand('make-rule', 'Blog', 'UniqueEmailRule');

        $this->assertFilenameExists('Blog/Infrastructure/Rules/UniqueEmailRule.php');
        $this->assertFileContains([
            'class UniqueEmailRule implements ValidationRule',
        ], 'Blog/Infrastructure/Rules/UniqueEmailRule.php');

        // Should NOT create double suffix
        $this->assertFilenameNotExists('Blog/Infrastructure/Rules/UniqueEmailRuleRule.php');
    }

    /**
     * Test that generated rules have proper validation structure
     */
    #[Test]
    public function it_generates_proper_validation_structure(): void
    {
        $this->runEasyModulesCommand('make-rule', 'Blog', 'ValidationTestRule');

        $this->assertFileContains([
            'public function validate(string $attribute, mixed $value, Closure $fail): void',
            '//',
        ], 'Blog/Infrastructure/Rules/ValidationTestRule.php');
    }
}
