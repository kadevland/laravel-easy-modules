<?php

declare(strict_types=1);

namespace Kadevland\EasyModules\Tests\Unit\Commands\Make\Laravel;

use Kadevland\EasyModules\Tests\Support\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Test suite for PolicyMakeCommand
 *
 * This command extends Laravel's base PolicyMakeCommand to generate
 * policies within the modular structure, supporting model resolution
 * and guard options.
 */
class PolicyMakeCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->setComponentPath('policy', 'Infrastructure/Policies');
        $this->setComponentPath('model', 'Infrastructure/Models');

        // Set app environment to testing to disable prompts
        $this->app['config']->set('app.env', 'testing');
    }

    protected function tearDown(): void
    {
        // Enhanced cleanup - remove all test files
        $testPaths = [
            'Blog/Infrastructure/Policies',
            'Blog/Infrastructure/Models',
            'Shop/Infrastructure/Policies',
            'Test/Infrastructure/Policies',
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
    // NIVEAU 5: BASIC POLICY GENERATION TESTS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Test basic policy file generation
     */
    #[Test]
    public function it_can_generate_basic_policy_file(): void
    {
        $this->runEasyModulesCommand('make-policy', 'Blog', 'PostPolicy');

        $this->assertFileContains([
            'namespace App\\Modules\\Blog\\Infrastructure\\Policies;',
            'use Illuminate\\Foundation\\Auth\\User;',
            'class PostPolicy',
        ], 'Blog/Infrastructure/Policies/PostPolicy.php');
    }

    /**
     * Test policy generation with model option
     */
    #[Test]
    public function it_can_generate_policy_with_model(): void
    {
        $this->runEasyModulesCommand('make-policy', 'Blog', 'PostPolicy', ['--model' => 'Post']);

        $this->assertFileContains([
            'class PostPolicy',
            'public function viewAny(User $user)',
            'public function view(User $user, Post $post)',
            'public function create(User $user)',
            'public function update(User $user, Post $post)',
            'public function delete(User $user, Post $post)',
        ], 'Blog/Infrastructure/Policies/PostPolicy.php');
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // NIVEAU 4: NAMESPACE CORRECTNESS TESTS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Test policy namespace and model references are correct
     */
    #[Test]
    public function it_generates_correct_policy_namespace_and_model_references(): void
    {
        $this->runEasyModulesCommand('make-policy', 'Blog', 'CommentPolicy', ['--model' => 'Comment']);

        $this->assertFileContains([
            'namespace App\\Modules\\Blog\\Infrastructure\\Policies;',
            'class CommentPolicy',
        ], 'Blog/Infrastructure/Policies/CommentPolicy.php');

        // Should NOT contain duplicated model references
        $this->assertFileNotContains([
            'App\\Modules\\Blog\\Infrastructure\\Models\\App\\Modules\\Blog\\Infrastructure\\Models\\Comment',
        ], 'Blog/Infrastructure/Policies/CommentPolicy.php');
    }

    /**
     * Test nested policy generation
     */
    #[Test]
    public function it_can_generate_nested_policies(): void
    {
        $this->runEasyModulesCommand('make-policy', 'Blog', 'Content/ArticlePolicy', ['--model' => 'Content/Article']);

        $this->assertFilenameExists('Blog/Infrastructure/Policies/Content/ArticlePolicy.php');
        $this->assertFileContains([
            'namespace App\\Modules\\Blog\\Infrastructure\\Policies\\Content;',
            'class ArticlePolicy',
        ], 'Blog/Infrastructure/Policies/Content/ArticlePolicy.php');
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // NIVEAU 3: DOUBLE PATH PREVENTION TESTS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Test prevention of double path and namespace duplication
     *
     * This test ensures that neither file paths nor namespaces are duplicated
     * when generating policies within the module structure.
     */
    #[Test]
    public function it_prevents_double_path_and_namespace_duplication(): void
    {
        $this->runEasyModulesCommand('make-policy', 'Blog', 'UserPolicy', ['--model' => 'User']);

        // File path verification
        $this->assertFilenameExists('Blog/Infrastructure/Policies/UserPolicy.php');
        $this->assertFilenameNotExists('Blog/Infrastructure/Policies/Infrastructure/Policies/UserPolicy.php');

        // Namespace duplication prevention
        $this->assertFileNotContains([
            'App\\Modules\\Blog\\Infrastructure\\Policies\\Infrastructure\\Policies',
            'App\\Modules\\Blog\\Blog\\Infrastructure\\Policies',
            'Infrastructure\\Policies\\Infrastructure\\Policies',
            'App\\Modules\\Blog\\Infrastructure\\Models\\Infrastructure\\Models\\User',
        ], 'Blog/Infrastructure/Policies/UserPolicy.php');
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // NIVEAU 2: CUSTOM CONFIGURATION TESTS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Test policy generation with custom module configuration
     */
    #[Test]
    public function it_handles_custom_module_configurations(): void
    {
        // Test with custom base namespace and paths
        $this->app['config']->set('easymodules.base_namespace', 'Custom\\Modules');
        $this->app['config']->set('easymodules.paths.policy', 'Domain/Policies');
        $this->app['config']->set('easymodules.paths.model', 'Domain/Entities');

        $this->runEasyModulesCommand('make-policy', 'Shop', 'ProductPolicy', ['--model' => 'Product']);

        $this->assertFilenameExists('Shop/Domain/Policies/ProductPolicy.php');
        $this->assertFileContains([
            'namespace Custom\\Modules\\Shop\\Domain\\Policies;',
            'class ProductPolicy',
        ], 'Shop/Domain/Policies/ProductPolicy.php');

        // Should NOT contain double namespace with custom config
        $this->assertFileNotContains([
            'Custom\\Modules\\Shop\\Domain\\Policies\\Domain\\Policies',
            'Custom\\Modules\\Shop\\Domain\\Entities\\Domain\\Entities\\Product',
        ], 'Shop/Domain/Policies/ProductPolicy.php');
    }

    /**
     * Test policy generation with different module structures
     */
    #[Test]
    public function it_handles_different_module_structures(): void
    {
        $testConfigs = [
            ['namespace' => 'App\\Components', 'policyPath' => 'Policies', 'modelPath' => 'Models'],
            ['namespace' => 'Custom\\App\\Modules', 'policyPath' => 'Security/Policies', 'modelPath' => 'Entities'],
        ];

        foreach ($testConfigs as $index => $config) {
            $this->app['config']->set('easymodules.base_namespace', $config['namespace']);
            $this->app['config']->set('easymodules.paths.policy', $config['policyPath']);
            $this->app['config']->set('easymodules.paths.model', $config['modelPath']);

            $policyName = "Test{$index}Policy";
            $modelName  = "Test{$index}";

            $this->runEasyModulesCommand('make-policy', 'Test', $policyName, ['--model' => $modelName]);

            $expectedPolicyPath = "Test/{$config['policyPath']}/{$policyName}.php";
            $this->assertFilenameExists($expectedPolicyPath);

            $expectedPolicyNamespace = str_replace('/', '\\', "{$config['namespace']}\\Test\\{$config['policyPath']}");
            $this->assertFileContains([
                "namespace {$expectedPolicyNamespace};",
                "class {$policyName}",
            ], $expectedPolicyPath);
        }
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // NIVEAU 1: EDGE CASE & INTEGRATION TESTS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Test policy generation with global model reference
     */
    #[Test]
    public function it_handles_global_model_references(): void
    {
        $this->runEasyModulesCommand('make-policy', 'Blog', 'UserPolicy', ['--model' => '\\App\\Models\\User']);

        $this->assertFilenameExists('Blog/Infrastructure/Policies/UserPolicy.php');
        $this->assertFileContains([
            'App\\Models\\User',
        ], 'Blog/Infrastructure/Policies/UserPolicy.php');

        // Should NOT try to modularize the global model
        $this->assertFileNotContains([
            'App\\Modules\\Blog\\Infrastructure\\Models\\App\\Models\\User',
        ], 'Blog/Infrastructure/Policies/UserPolicy.php');
    }

    /**
     * Test policy generation with complex model names
     */
    #[Test]
    public function it_handles_complex_model_names(): void
    {
        $complexCases = [
            ['policy' => 'BlogPostPolicy', 'model' => 'BlogPost'],
            ['policy' => 'UserProfilePolicy', 'model' => 'UserProfile'],
        ];

        foreach ($complexCases as $case) {
            $this->runEasyModulesCommand('make-policy', 'Test', $case['policy'], ['--model' => $case['model']]);

            $this->assertFilenameExists("Test/Infrastructure/Policies/{$case['policy']}.php");
            $this->assertFileContains([
                "class {$case['policy']}",
                "App\\Modules\\Test\\Infrastructure\\Models\\{$case['model']}",
            ], "Test/Infrastructure/Policies/{$case['policy']}.php");
        }
    }

    /**
     * Test policy generation with custom guard
     */
    #[Test]
    public function it_handles_custom_guard_option(): void
    {
        $this->app['config']->set('auth.guards.admin', [
            'driver'   => 'session',
            'provider' => 'users',
        ]);

        $this->runEasyModulesCommand('make-policy', 'Blog', 'AdminPolicy', [
            '--model' => 'Post',
            '--guard' => 'admin',
        ]);

        $this->assertFilenameExists('Blog/Infrastructure/Policies/AdminPolicy.php');
        $this->assertFileContains([
            'class AdminPolicy',
            'Post $post',
        ], 'Blog/Infrastructure/Policies/AdminPolicy.php');
    }

    /**
     * Test policy generation edge cases
     */
    #[Test]
    public function it_handles_edge_cases_gracefully(): void
    {
        // Test with single character names
        $this->runEasyModulesCommand('make-policy', 'Blog', 'APolicy', ['--model' => 'A']);

        $this->assertFilenameExists('Blog/Infrastructure/Policies/APolicy.php');
        $this->assertFileContains([
            'App\\Modules\\Blog\\Infrastructure\\Models\\A',
            'A $a',
        ], 'Blog/Infrastructure/Policies/APolicy.php');
    }

    /**
     * Test policy works with various module structures
     */
    #[Test]
    public function it_works_with_various_module_structures(): void
    {
        $modules = ['Blog', 'Shop', 'Admin'];

        foreach ($modules as $module) {
            $this->runEasyModulesCommand('make-policy', $module, 'TestPolicy', ['--model' => 'Test']);

            $this->assertFilenameExists("{$module}/Infrastructure/Policies/TestPolicy.php");
            $this->assertFileContains([
                "namespace App\\Modules\\{$module}\\Infrastructure\\Policies;",
                'class TestPolicy',
                "App\\Modules\\{$module}\\Infrastructure\\Models\\Test",
            ], "{$module}/Infrastructure/Policies/TestPolicy.php");
        }
    }

    /**
     * Test multiple policies in same module
     */
    #[Test]
    public function it_handles_multiple_policies_in_same_module(): void
    {
        $policies = [
            ['name' => 'PostPolicy', 'model' => 'Post'],
            ['name' => 'CommentPolicy', 'model' => 'Comment'],
            ['name' => 'CategoryPolicy', 'model' => 'Category'],
        ];

        foreach ($policies as $policy) {
            $this->runEasyModulesCommand('make-policy', 'Blog', $policy['name'], ['--model' => $policy['model']]);
            $this->assertFilenameExists("Blog/Infrastructure/Policies/{$policy['name']}.php");
        }

        // Verify all files exist and have correct content
        foreach ($policies as $policy) {
            $this->assertFileContains([
                "class {$policy['name']}",
                "App\\Modules\\Blog\\Infrastructure\\Models\\{$policy['model']}",
            ], "Blog/Infrastructure/Policies/{$policy['name']}.php");
        }
    }
}
