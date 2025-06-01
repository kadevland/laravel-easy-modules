<?php

declare(strict_types=1);

namespace Kadevland\EasyModules\Tests\Unit\Commands\Make\Laravel;

use Kadevland\EasyModules\Tests\Support\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Test suite for FactoryMakeCommand
 *
 * This command extends Laravel's base FactoryMakeCommand to generate
 * model factories within the modular structure, supporting model
 * resolution and proper namespace handling.
 */
class FactoryMakeCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->setComponentPath('factory', 'Database/Factories');
        $this->setComponentPath('model', 'Infrastructure/Models');

        // Set app environment to testing to disable prompts
        $this->app['config']->set('app.env', 'testing');
    }

    protected function tearDown(): void
    {
        // Enhanced cleanup - remove all test files
        $testPaths = [
            'Blog/Database/Factories',
            'Blog/Infrastructure/Models',
            'Shop/Database/Factories',
            'Shop/Infrastructure/Models',
            'Test/Database/Factories',
            'Custom/Modules/Shop/Infrastructure/Factories',
            'Custom/Modules/Shop/Domain/Entities',
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
    // NIVEAU 5: BASIC FACTORY GENERATION TESTS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Test basic factory file generation
     */
    #[Test]
    public function it_can_generate_basic_factory_file(): void
    {
        $this->runEasyModulesCommand('make-factory', 'Blog', 'PostFactory');

        $this->assertModuleComponentExists('Blog', 'Database/Factories', 'PostFactory', [
            'use Illuminate\Database\Eloquent\Factories\Factory;',
            'class PostFactory extends Factory',
            'public function definition()',
        ]);
    }

    /**
     * Test factory generation with automatic Model suffix
     */
    #[Test]
    public function it_can_generate_factory_with_auto_suffix(): void
    {
        $this->runEasyModulesCommand('make-factory', 'Blog', 'UserFactory');

        $this->assertFilenameExists('Blog/Database/Factories/UserFactory.php');
        $this->assertFileContains([
            'namespace App\\Modules\\Blog\\Database\\Factories;',
            'class UserFactory extends Factory',
        ], 'Blog/Database/Factories/UserFactory.php');
    }

    /**
     * Test factory generation with explicit model option
     */
    #[Test]
    public function it_can_generate_factory_with_model_option(): void
    {
        $this->runEasyModulesCommand('make-factory', 'Blog', 'PostFactory', ['--model' => 'Post']);

        $this->assertModuleComponentExists('Blog', 'Database/Factories', 'PostFactory', [
            'use Illuminate\Database\Eloquent\Factories\Factory;',
            'class PostFactory extends Factory',
        ]);

        // Check the namespace and model reference are correct
        $this->assertFileContains([
            'namespace App\\Modules\\Blog\\Database\\Factories;',
        ], 'Blog/Database/Factories/PostFactory.php');

        // Check that double namespace is NOT present
        $this->assertFileNotContains([
            'App\\Modules\\Blog\\Infrastructure\\Models\\App\\Modules\\Blog\\Infrastructure\\Models\\Post',
        ], 'Blog/Database/Factories/PostFactory.php');
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // NIVEAU 4: NAMESPACE CORRECTNESS TESTS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Test factory generation with model inference from name
     */
    #[Test]
    public function it_can_infer_model_from_factory_name(): void
    {
        $this->runEasyModulesCommand('make-factory', 'Blog', 'CategoryFactory');

        $this->assertFilenameExists('Blog/Database/Factories/CategoryFactory.php');

        // Should reference the Category model correctly
        $this->assertFileContains([
            'namespace App\\Modules\\Blog\\Database\\Factories;',
            'class CategoryFactory extends Factory',
        ], 'Blog/Database/Factories/CategoryFactory.php');
    }

    /**
     * Test factory generation with nested model names
     */
    #[Test]
    public function it_can_generate_factory_for_nested_models(): void
    {
        $this->runEasyModulesCommand('make-factory', 'Blog', 'Category/PostFactory', ['--model' => 'Category/Post']);

        // Should create nested directory structure
        $this->assertFilenameExists('Blog/Database/Factories/Category/PostFactory.php');

        $this->assertFileContains([
            'namespace App\\Modules\\Blog\\Database\\Factories\\Category;',
            'class PostFactory extends Factory',
        ], 'Blog/Database/Factories/Category/PostFactory.php');
    }

    /**
     * Test factory namespace generation is correct
     */
    #[Test]
    public function it_generates_correct_factory_namespace(): void
    {
        $this->runEasyModulesCommand('make-factory', 'Blog', 'CommentFactory', ['--model' => 'Comment']);

        // Factory should have correct namespace
        $this->assertFileContains([
            'namespace App\\Modules\\Blog\\Database\\Factories;',
        ], 'Blog/Database/Factories/CommentFactory.php');

        // Should NOT contain duplicated namespace segments
        $this->assertFileNotContains([
            'namespace App\\Modules\\Blog\\Database\\Factories\\Database\\Factories;',
            'namespace App\\Modules\\Blog\\Blog\\Database\\Factories;',
        ], 'Blog/Database/Factories/CommentFactory.php');
    }

    /**
     * Test model reference in factory is correct
     */
    #[Test]
    public function it_generates_correct_model_references(): void
    {
        $this->runEasyModulesCommand('make-factory', 'Blog', 'TagFactory', ['--model' => 'Tag']);

        $this->assertFileContains([
            'App\\Modules\\Blog\\Infrastructure\\Models\\Tag',
        ], 'Blog/Database/Factories/TagFactory.php');

        // Should NOT contain double namespace in any model references
        $this->assertFileNotContains([
            'App\\Modules\\Blog\\Infrastructure\\Models\\App\\Modules\\Blog\\Infrastructure\\Models\\Tag',
        ], 'Blog/Database/Factories/TagFactory.php');
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // NIVEAU 3: DOUBLE PATH PREVENTION TESTS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Test prevention of double path and namespace duplication
     *
     * This test ensures that neither file paths nor namespaces are duplicated
     * when generating factories within the module structure.
     */
    #[Test]
    public function it_prevents_double_path_and_namespace_duplication(): void
    {
        $this->runEasyModulesCommand('make-factory', 'Blog', 'ArticleFactory', ['--model' => 'Article']);

        // ✅ File path verification
        $this->assertFilenameExists('Blog/Database/Factories/ArticleFactory.php');
        $this->assertFilenameNotExists('Blog/Database/Factories/Database/Factories/ArticleFactory.php');

        // ✅ Namespace duplication prevention
        $this->assertFileNotContains([
            'App\\Modules\\Blog\\Database\\Factories\\Database\\Factories',
            'App\\Modules\\Blog\\Blog\\Database\\Factories',
            'Database\\Factories\\Database\\Factories',
            'App\\Modules\\Blog\\Infrastructure\\Models\\App\\Modules\\Blog\\Infrastructure\\Models\\Article',
        ], 'Blog/Database/Factories/ArticleFactory.php');
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // NIVEAU 2: CUSTOM CONFIGURATION TESTS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Test factory generation with custom module configuration
     */
    #[Test]
    public function it_handles_custom_module_configurations(): void
    {
        // Test with custom base namespace and paths
        $this->app['config']->set('easymodules.base_namespace', 'Custom\\Modules');
        $this->app['config']->set('easymodules.paths.factory', 'Infrastructure/Factories');
        $this->app['config']->set('easymodules.paths.model', 'Domain/Entities');

        $this->runEasyModulesCommand('make-factory', 'Shop', 'ProductFactory', ['--model' => 'Product']);

        // Check custom paths are used
        $this->assertFilenameExists('Shop/Infrastructure/Factories/ProductFactory.php');

        // Check custom namespaces
        $this->assertFileContains([
            'namespace Custom\\Modules\\Shop\\Infrastructure\\Factories;',
            'class ProductFactory extends Factory',
        ], 'Shop/Infrastructure/Factories/ProductFactory.php');

        // Should reference custom model namespace
        $this->assertFileContains([
            'Custom\\Modules\\Shop\\Domain\\Entities\\Product',
        ], 'Shop/Infrastructure/Factories/ProductFactory.php');

        // Should NOT contain double namespace with custom config
        $this->assertFileNotContains([
            'Custom\\Modules\\Shop\\Domain\\Entities\\Custom\\Modules\\Shop\\Domain\\Entities\\Product',
        ], 'Shop/Infrastructure/Factories/ProductFactory.php');
    }

    /**
     * Test factory generation with different module structures
     */
    #[Test]
    public function it_handles_different_module_structures(): void
    {
        $testConfigs = [
            ['namespace' => 'App\\Components', 'factoryPath' => 'Factories', 'modelPath' => 'Models'],
            ['namespace' => 'Modules', 'factoryPath' => 'Database/Factories', 'modelPath' => 'Entities'],
            ['namespace' => 'Custom\\App\\Modules', 'factoryPath' => 'Data/Factories', 'modelPath' => 'Domain/Models'],
        ];

        foreach ($testConfigs as $index => $config) {
            $this->app['config']->set('easymodules.base_namespace', $config['namespace']);
            $this->app['config']->set('easymodules.paths.factory', $config['factoryPath']);
            $this->app['config']->set('easymodules.paths.model', $config['modelPath']);

            $factoryName = "Test{$index}Factory";
            $modelName   = "Test{$index}";

            $this->runEasyModulesCommand('make-factory', 'Test', $factoryName, ['--model' => $modelName]);

            $expectedFactoryPath = "Test/{$config['factoryPath']}/{$factoryName}.php";
            $this->assertFilenameExists($expectedFactoryPath);

            $expectedFactoryNamespace = str_replace('/', '\\', "{$config['namespace']}\\Test\\{$config['factoryPath']}");
            $expectedModelNamespace   = str_replace('/', '\\', "{$config['namespace']}\\Test\\{$config['modelPath']}\\{$modelName}");

            $this->assertFileContains([
                "namespace {$expectedFactoryNamespace};",
                "class {$factoryName}",
                $expectedModelNamespace,
            ], $expectedFactoryPath);

            // Should not contain duplicated path segments
            $pathSegments = explode('/', $config['factoryPath']);

            $duplicatedPatterns = [];
            foreach ($pathSegments as $segment) {
                $duplicatedPatterns[] = "{$expectedFactoryNamespace}\\{$segment}\\{$segment}";
            }

            $this->assertFileNotContains($duplicatedPatterns, $expectedFactoryPath);
        }
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // NIVEAU 1: EDGE CASE & INTEGRATION TESTS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Test factory generation without explicit model option
     */
    #[Test]
    public function it_infers_model_name_when_not_specified(): void
    {
        // Factory name ends with "Factory", should infer model name
        $this->runEasyModulesCommand('make-factory', 'Blog', 'AuthorFactory');

        $this->assertFilenameExists('Blog/Database/Factories/AuthorFactory.php');

        // Should infer "Author" as the model name
        $this->assertFileContains([
            'App\\Modules\\Blog\\Infrastructure\\Models\\Author',
        ], 'Blog/Database/Factories/AuthorFactory.php');
    }

    /**
     * Test factory generation with explicit model path
     */
    #[Test]
    public function it_handles_explicit_model_paths(): void
    {
        $this->runEasyModulesCommand('make-factory', 'Blog', 'Content/ArticleFactory', ['--model' => 'Content/Article']);

        $this->assertFilenameExists('Blog/Database/Factories/Content/ArticleFactory.php');

        $this->assertFileContains([
            'namespace App\\Modules\\Blog\\Database\\Factories\\Content;',
            'class ArticleFactory extends Factory',
        ], 'Blog/Database/Factories/Content/ArticleFactory.php');

        // Main concern: Should NOT contain double namespace patterns
        $this->assertFileNotContains([
            'App\\Modules\\Blog\\Infrastructure\\Models\\App\\Modules\\Blog\\Infrastructure\\Models\\Content',
        ], 'Blog/Database/Factories/Content/ArticleFactory.php');
    }

    /**
     * Test factory generation with complex model names
     */
    #[Test]
    public function it_handles_complex_model_names(): void
    {
        $complexCases = [
            'UserProfileFactory' => 'UserProfile',
            'BlogPostFactory'    => 'BlogPost',
            'AdminUserFactory'   => 'AdminUser',
        ];

        foreach ($complexCases as $factoryName => $expectedModel) {
            $this->runEasyModulesCommand('make-factory', 'Test', $factoryName);

            $this->assertFilenameExists("Test/Database/Factories/{$factoryName}.php");

            // Should correctly infer model name
            $this->assertFileContains([
                "App\\Modules\\Test\\Infrastructure\\Models\\{$expectedModel}",
            ], "Test/Database/Factories/{$factoryName}.php");
        }
    }

    /**
     * Test factory generation edge cases
     */
    #[Test]
    public function it_handles_edge_cases_gracefully(): void
    {
        // Test with empty model name
        $this->runEasyModulesCommand('make-factory', 'Blog', 'EmptyFactory', ['--model' => '']);

        $this->assertFilenameExists('Blog/Database/Factories/EmptyFactory.php');

        // Test with single character names
        $this->runEasyModulesCommand('make-factory', 'Blog', 'AFactory', ['--model' => 'A']);

        $this->assertFilenameExists('Blog/Database/Factories/AFactory.php');
        $this->assertFileContains([
            'App\\Modules\\Blog\\Infrastructure\\Models\\A',
        ], 'Blog/Database/Factories/AFactory.php');
    }

    /**
     * Test factory works with various module structures
     */
    #[Test]
    public function it_works_with_various_module_structures(): void
    {
        // Test multiple modules
        $modules = ['Blog', 'Shop', 'Admin'];

        foreach ($modules as $module) {
            $this->runEasyModulesCommand('make-factory', $module, 'TestFactory', ['--model' => 'Test']);

            $this->assertFilenameExists("{$module}/Database/Factories/TestFactory.php");

            $this->assertFileContains([
                "namespace App\\Modules\\{$module}\\Database\\Factories;",
                'class TestFactory extends Factory',
            ], "{$module}/Database/Factories/TestFactory.php");
        }
    }

    /**
     * Test multiple factories in same module
     */
    #[Test]
    public function it_handles_multiple_factories_in_same_module(): void
    {
        $factories = [
            ['name' => 'UserFactory', 'model' => 'User'],
            ['name' => 'PostFactory', 'model' => 'Post'],
            ['name' => 'CommentFactory', 'model' => 'Comment'],
        ];

        foreach ($factories as $factory) {
            $this->runEasyModulesCommand('make-factory', 'Blog', $factory['name'], ['--model' => $factory['model']]);
            $this->assertFilenameExists("Blog/Database/Factories/{$factory['name']}.php");
        }

        // Verify all files exist and have correct content
        foreach ($factories as $factory) {
            $this->assertFileContains([
                "class {$factory['name']}",
                "App\\Modules\\Blog\\Infrastructure\\Models\\{$factory['model']}",
            ], "Blog/Database/Factories/{$factory['name']}.php");
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
        $this->app['config']->set('easymodules.suffixes.factory', 'Factory');

        // Test suffix addition when missing
        $this->runEasyModulesCommand('make-factory', 'Blog', 'User', ['--model' => 'User']);

        $this->assertFilenameExists('Blog/Database/Factories/UserFactory.php');
        $this->assertFileContains([
            'class UserFactory extends Factory',
        ], 'Blog/Database/Factories/UserFactory.php');

        // Test suffix not duplicated when already present
        $this->runEasyModulesCommand('make-factory', 'Blog', 'PostFactory', ['--model' => 'Post']);

        $this->assertFilenameExists('Blog/Database/Factories/PostFactory.php');
        $this->assertFileContains([
            'class PostFactory extends Factory',
        ], 'Blog/Database/Factories/PostFactory.php');

        // Should NOT create double suffix
        $this->assertFilenameNotExists('Blog/Database/Factories/PostFactoryFactory.php');
    }
}
