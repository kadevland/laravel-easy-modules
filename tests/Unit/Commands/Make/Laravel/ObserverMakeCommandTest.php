<?php

declare(strict_types=1);

namespace Kadevland\EasyModules\Tests\Unit\Commands\Make\Laravel;

use Kadevland\EasyModules\Tests\Support\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Test suite for ObserverMakeCommand
 *
 * This command extends Laravel's base ObserverMakeCommand to generate
 * model observers within the modular structure, supporting model
 * resolution and event method generation.
 */
class ObserverMakeCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->setComponentPath('observer', 'Infrastructure/Observers');
        $this->setComponentPath('model', 'Infrastructure/Models');

        // Set app environment to testing to disable prompts
        $this->app['config']->set('app.env', 'testing');
    }

    protected function tearDown(): void
    {
        // Enhanced cleanup - remove all test files
        $testPaths = [
            'Blog/Infrastructure/Observers',
            'Blog/Infrastructure/Models',
            'Shop/Infrastructure/Observers',
            'Test/Infrastructure/Observers',
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
    // NIVEAU 5: BASIC OBSERVER GENERATION TESTS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Test basic observer file generation
     */
    #[Test]
    public function it_can_generate_basic_observer_file(): void
    {
        $this->runEasyModulesCommand('make-observer', 'Blog', 'PostObserver');

        $this->assertFileContains([
            'namespace App\\Modules\\Blog\\Infrastructure\\Observers;',
            'class PostObserver',
        ], 'Blog/Infrastructure/Observers/PostObserver.php');
    }

    /**
     * Test observer generation with model option
     */
    #[Test]
    public function it_can_generate_observer_with_model(): void
    {
        $this->runEasyModulesCommand('make-observer', 'Blog', 'PostObserver', ['--model' => 'Post']);

        $this->assertFileContains([
            'class PostObserver',
            'public function created(Post $post)',
            'public function updated(Post $post)',
            'public function deleted(Post $post)',
            'public function restored(Post $post)',
            'public function forceDeleted(Post $post)',
        ], 'Blog/Infrastructure/Observers/PostObserver.php');
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // NIVEAU 4: NAMESPACE CORRECTNESS TESTS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Test observer namespace and model references are correct
     */
    #[Test]
    public function it_generates_correct_observer_namespace_and_model_references(): void
    {
        $this->runEasyModulesCommand('make-observer', 'Blog', 'CommentObserver', ['--model' => 'Comment']);

        $this->assertFileContains([
            'namespace App\\Modules\\Blog\\Infrastructure\\Observers;',
            'class CommentObserver',
        ], 'Blog/Infrastructure/Observers/CommentObserver.php');

        // Should NOT contain duplicated model references
        $this->assertFileNotContains([
            'App\\Modules\\Blog\\Infrastructure\\Models\\App\\Modules\\Blog\\Infrastructure\\Models\\Comment',
        ], 'Blog/Infrastructure/Observers/CommentObserver.php');
    }

    /**
     * Test observer method structure generation
     */
    #[Test]
    public function it_generates_correct_observer_method_structure(): void
    {
        $this->runEasyModulesCommand('make-observer', 'Blog', 'ArticleObserver', ['--model' => 'Article']);

        $this->assertFileContains([
            'public function created(Article $article): void',
            'public function updated(Article $article): void',
            'public function deleted(Article $article): void',
        ], 'Blog/Infrastructure/Observers/ArticleObserver.php');
    }

    /**
     * Test nested observer generation
     */
    #[Test]
    public function it_can_generate_nested_observers(): void
    {
        $this->runEasyModulesCommand('make-observer', 'Blog', 'Content/ArticleObserver', ['--model' => 'Content/Article']);

        $this->assertFilenameExists('Blog/Infrastructure/Observers/Content/ArticleObserver.php');
        $this->assertFileContains([
            'namespace App\\Modules\\Blog\\Infrastructure\\Observers\\Content;',
            'class ArticleObserver',
        ], 'Blog/Infrastructure/Observers/Content/ArticleObserver.php');
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // NIVEAU 3: DOUBLE PATH PREVENTION TESTS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Test prevention of double path and namespace duplication
     *
     * This test ensures that neither file paths nor namespaces are duplicated
     * when generating observers within the module structure.
     */
    #[Test]
    public function it_prevents_double_path_and_namespace_duplication(): void
    {
        $this->runEasyModulesCommand('make-observer', 'Blog', 'UserObserver', ['--model' => 'User']);

        // File path verification
        $this->assertFilenameExists('Blog/Infrastructure/Observers/UserObserver.php');
        $this->assertFilenameNotExists('Blog/Infrastructure/Observers/Infrastructure/Observers/UserObserver.php');

        // Namespace duplication prevention
        $this->assertFileNotContains([
            'App\\Modules\\Blog\\Infrastructure\\Observers\\Infrastructure\\Observers',
            'App\\Modules\\Blog\\Blog\\Infrastructure\\Observers',
            'Infrastructure\\Observers\\Infrastructure\\Observers',
            'App\\Modules\\Blog\\Infrastructure\\Models\\Infrastructure\\Models\\User',
        ], 'Blog/Infrastructure/Observers/UserObserver.php');
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // NIVEAU 2: CUSTOM CONFIGURATION TESTS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Test observer generation with custom module configuration
     */
    #[Test]
    public function it_handles_custom_module_configurations(): void
    {
        // Test with custom base namespace and paths
        $this->app['config']->set('easymodules.base_namespace', 'Custom\\Modules');
        $this->app['config']->set('easymodules.paths.observer', 'Domain/Observers');
        $this->app['config']->set('easymodules.paths.model', 'Domain/Entities');

        $this->runEasyModulesCommand('make-observer', 'Shop', 'ProductObserver', ['--model' => 'Product']);

        $this->assertFilenameExists('Shop/Domain/Observers/ProductObserver.php');
        $this->assertFileContains([
            'namespace Custom\\Modules\\Shop\\Domain\\Observers;',
            'class ProductObserver',
        ], 'Shop/Domain/Observers/ProductObserver.php');

        // Should NOT contain double namespace with custom config
        $this->assertFileNotContains([
            'Custom\\Modules\\Shop\\Domain\\Observers\\Domain\\Observers',
            'Custom\\Modules\\Shop\\Domain\\Entities\\Domain\\Entities\\Product',
        ], 'Shop/Domain/Observers/ProductObserver.php');
    }

    /**
     * Test observer generation with different module structures
     */
    #[Test]
    public function it_handles_different_module_structures(): void
    {
        $testConfigs = [
            ['namespace' => 'App\\Components', 'observerPath' => 'Observers', 'modelPath' => 'Models'],
            ['namespace' => 'Custom\\App\\Modules', 'observerPath' => 'Event/Observers', 'modelPath' => 'Entities'],
        ];

        foreach ($testConfigs as $index => $config) {
            $this->app['config']->set('easymodules.base_namespace', $config['namespace']);
            $this->app['config']->set('easymodules.paths.observer', $config['observerPath']);
            $this->app['config']->set('easymodules.paths.model', $config['modelPath']);

            $observerName = "Test{$index}Observer";
            $modelName    = "Test{$index}";

            $this->runEasyModulesCommand('make-observer', 'Test', $observerName, ['--model' => $modelName]);

            $expectedObserverPath = "Test/{$config['observerPath']}/{$observerName}.php";
            $this->assertFilenameExists($expectedObserverPath);

            $expectedObserverNamespace = str_replace('/', '\\', "{$config['namespace']}\\Test\\{$config['observerPath']}");
            $this->assertFileContains([
                "namespace {$expectedObserverNamespace};",
                "class {$observerName}",
            ], $expectedObserverPath);
        }
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // NIVEAU 1: EDGE CASE & INTEGRATION TESTS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Test observer generation with global model reference
     */
    #[Test]
    public function it_handles_global_model_references(): void
    {
        $this->runEasyModulesCommand('make-observer', 'Blog', 'UserObserver', ['--model' => '\\App\\Models\\User']);

        $this->assertFilenameExists('Blog/Infrastructure/Observers/UserObserver.php');
        $this->assertFileContains([
            'App\\Models\\User',
        ], 'Blog/Infrastructure/Observers/UserObserver.php');

        // Should NOT try to modularize the global model
        $this->assertFileNotContains([
            'App\\Modules\\Blog\\Infrastructure\\Models\\App\\Models\\User',
        ], 'Blog/Infrastructure/Observers/UserObserver.php');
    }

    /**
     * Test observer generation with complex model names
     */
    #[Test]
    public function it_handles_complex_model_names(): void
    {
        $complexCases = [
            ['observer' => 'BlogPostObserver', 'model' => 'BlogPost', 'var' => 'blogPost'],
            ['observer' => 'UserProfileObserver', 'model' => 'UserProfile', 'var' => 'userProfile'],
        ];

        foreach ($complexCases as $case) {
            $this->runEasyModulesCommand('make-observer', 'Test', $case['observer'], ['--model' => $case['model']]);

            $this->assertFilenameExists("Test/Infrastructure/Observers/{$case['observer']}.php");
            $this->assertFileContains([
                "class {$case['observer']}",
                "App\\Modules\\Test\\Infrastructure\\Models\\{$case['model']}",
                "{$case['model']} \${$case['var']}",
            ], "Test/Infrastructure/Observers/{$case['observer']}.php");
        }
    }

    /**
     * Test observer method generation patterns
     */
    #[Test]
    public function it_generates_standard_observer_methods(): void
    {
        $this->runEasyModulesCommand('make-observer', 'Blog', 'CompleteObserver', ['--model' => 'Post']);

        $expectedMethods = [
            'public function created(Post $post)',
            'public function updated(Post $post)',
            'public function deleted(Post $post)',
            'public function restored(Post $post)',
            'public function forceDeleted(Post $post)',
        ];

        foreach ($expectedMethods as $method) {
            $this->assertFileContains([$method], 'Blog/Infrastructure/Observers/CompleteObserver.php');
        }

        // Should have proper return type hints
        $this->assertFileContains([
            'public function created(Post $post): void',
            'public function updated(Post $post): void',
        ], 'Blog/Infrastructure/Observers/CompleteObserver.php');
    }

    /**
     * Test observer generation edge cases
     */
    #[Test]
    public function it_handles_edge_cases_gracefully(): void
    {
        // Test with single character names
        $this->runEasyModulesCommand('make-observer', 'Blog', 'AObserver', ['--model' => 'A']);

        $this->assertFilenameExists('Blog/Infrastructure/Observers/AObserver.php');
        $this->assertFileContains([
            'App\\Modules\\Blog\\Infrastructure\\Models\\A',
            'A $a',
        ], 'Blog/Infrastructure/Observers/AObserver.php');
    }

    /**
     * Test observer works with various module structures
     */
    #[Test]
    public function it_works_with_various_module_structures(): void
    {
        $modules = ['Blog', 'Shop', 'Admin'];

        foreach ($modules as $module) {
            $this->runEasyModulesCommand('make-observer', $module, 'TestObserver', ['--model' => 'Test']);

            $this->assertFilenameExists("{$module}/Infrastructure/Observers/TestObserver.php");
            $this->assertFileContains([
                "namespace App\\Modules\\{$module}\\Infrastructure\\Observers;",
                'class TestObserver',
                "App\\Modules\\{$module}\\Infrastructure\\Models\\Test",
            ], "{$module}/Infrastructure/Observers/TestObserver.php");
        }
    }

    /**
     * Test multiple observers in same module
     */
    #[Test]
    public function it_handles_multiple_observers_in_same_module(): void
    {
        $observers = [
            ['name' => 'PostObserver', 'model' => 'Post'],
            ['name' => 'CommentObserver', 'model' => 'Comment'],
            ['name' => 'UserObserver', 'model' => 'User'],
        ];

        foreach ($observers as $observer) {
            $this->runEasyModulesCommand('make-observer', 'Blog', $observer['name'], ['--model' => $observer['model']]);
            $this->assertFilenameExists("Blog/Infrastructure/Observers/{$observer['name']}.php");
        }

        // Verify all files exist and have correct content
        foreach ($observers as $observer) {
            $this->assertFileContains([
                "class {$observer['name']}",
                "App\\Modules\\Blog\\Infrastructure\\Models\\{$observer['model']}",
            ], "Blog/Infrastructure/Observers/{$observer['name']}.php");
        }
    }

    /**
     * Test observer with include-global option support
     */
    #[Test]
    public function it_supports_include_global_option(): void
    {
        $this->runEasyModulesCommand('make-observer', 'Blog', 'GlobalObserver', [
            '--model'          => 'Post',
            '--include-global' => true,
        ]);

        $this->assertFilenameExists('Blog/Infrastructure/Observers/GlobalObserver.php');
        $this->assertFileContains([
            'App\\Modules\\Blog\\Infrastructure\\Models\\Post',
        ], 'Blog/Infrastructure/Observers/GlobalObserver.php');
    }
}
