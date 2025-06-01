<?php

declare(strict_types=1);

namespace Kadevland\EasyModules\Tests\Unit\Commands\Make\Laravel;

use Kadevland\EasyModules\Tests\Support\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Test suite for ModelMakeCommand
 *
 * This command extends Laravel's base ModelMakeCommand to generate
 * models within the modular structure, supporting ALL Laravel options
 * and preventing double path generation bugs.
 */
class ModelMakeCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->setComponentPath('model', 'Infrastructure/Models');
        $this->setComponentPath('factory', 'Database/Factories');
        $this->setComponentPath('migration', 'Database/Migrations');
        $this->setComponentPath('seeder', 'Database/Seeders');
        $this->setComponentPath('controller', 'Presentation/Http/Controllers');
        $this->setComponentPath('policy', 'Infrastructure/Policies');
        $this->setComponentPath('request', 'Presentation/Http/Requests');

        // Set app environment to testing to disable prompts
        $this->app['config']->set('app.env', 'testing');
    }

    protected function tearDown(): void
    {
        // Enhanced cleanup - remove all test files
        $testPaths = [
            'Blog/Infrastructure/Models',
            'Blog/Database/Factories',
            'Blog/Database/Migrations',
            'Blog/Database/Seeders',
            'Blog/Presentation/Http/Controllers',
            'Blog/Infrastructure/Policies',
            'Blog/Presentation/Http/Requests',
            'Shop/Domain/Entities',
            'Shop/Infrastructure/Factories',
            'Test/Infrastructure/Models',
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
    // NIVEAU 5: BASIC MODEL GENERATION TESTS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Test basic model file generation
     */
    #[Test]
    public function it_can_generate_basic_model_file(): void
    {
        $this->runEasyModulesCommand('make-model', 'Blog', 'Post');

        $this->assertModuleComponentExists('Blog', 'Infrastructure/Models', 'Post', [
            'use Illuminate\Database\Eloquent\Model;',
            'class Post extends Model',
        ]);
    }

    /**
     * Test pivot model generation
     */
    #[Test]
    public function it_can_generate_pivot_model_file(): void
    {
        $this->runEasyModulesCommand('make-model', 'Blog', 'PostTag', ['--pivot' => true]);

        $this->assertModuleComponentExists('Blog', 'Infrastructure/Models', 'PostTag', [
            'use Illuminate\Database\Eloquent\Relations\Pivot;',
            'class PostTag extends Pivot',
        ]);
    }

    /**
     * Test morph pivot model generation
     */
    #[Test]
    public function it_can_generate_morph_pivot_model_file(): void
    {
        $this->runEasyModulesCommand('make-model', 'Blog', 'Taggable', ['--morph-pivot' => true]);

        $this->assertModuleComponentExists('Blog', 'Infrastructure/Models', 'Taggable', [
            'use Illuminate\Database\Eloquent\Relations\MorphPivot;',
            'class Taggable extends MorphPivot',
        ]);
    }

    /**
     * Test model with factory generation
     */
    #[Test]
    public function it_can_generate_model_with_factory(): void
    {
        $this->runEasyModulesCommand('make-model', 'Blog', 'Post', ['--factory' => true]);

        // Check model has HasFactory trait
        $this->assertModuleComponentExists('Blog', 'Infrastructure/Models', 'Post', [
            'use Illuminate\Database\Eloquent\Factories\HasFactory;',
            'use Illuminate\Database\Eloquent\Model;',
            'class Post extends Model',
            'use HasFactory;',
        ]);

        // Check factory was created in module
        $this->assertFilenameExists('Blog/Database/Factories/PostFactory.php');

        // Check factory namespace is correct (not duplicated)
        $this->assertFileContains([
            'namespace App\\Modules\\Blog\\Database\\Factories;',
            'class PostFactory extends Factory',
        ], 'Blog/Database/Factories/PostFactory.php');

        // Check factory does NOT contain double namespace in use statement
        $this->assertFileNotContains([
            'use App\\Modules\\Blog\\Infrastructure\\Models\\App\\Modules\\Blog\\Infrastructure\\Models\\Post;',
        ], 'Blog/Database/Factories/PostFactory.php');
    }

    /**
     * Test factory generation with short flag
     */
    #[Test]
    public function it_can_generate_model_with_factory_short_flag(): void
    {
        $this->runEasyModulesCommand('make-model', 'Blog', 'Comment', ['-f' => true]);

        $this->assertFilenameExists('Blog/Database/Factories/CommentFactory.php');
        $this->assertModuleComponentExists('Blog', 'Infrastructure/Models', 'Comment', [
            'use HasFactory;',
        ]);
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // NIVEAU 4: NAMESPACE CORRECTNESS TESTS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Test model with migration generation
     */
    #[Test]
    public function it_can_generate_model_with_migration(): void
    {
        $this->runEasyModulesCommand('make-model', 'Blog', 'Post', ['--migration' => true]);

        // Check model exists
        $this->assertFilenameExists('Blog/Infrastructure/Models/Post.php');

        // Check migration was created (with timestamp prefix)
        $migrationFiles = glob($this->testBasePath('Blog/Database/Migrations/*_create_posts_table.php'));
        $this->assertNotEmpty($migrationFiles, 'Migration file should be created');

        $migrationContent = file_get_contents($migrationFiles[0]);
        $this->assertStringContainsString('use Illuminate\Database\Migrations\Migration;', $migrationContent);
        $this->assertStringContainsString('return new class extends Migration', $migrationContent);
        $this->assertStringContainsString("Schema::create('posts'", $migrationContent);
    }

    /**
     * Test migration generation with short flag
     */
    #[Test]
    public function it_can_generate_model_with_migration_short_flag(): void
    {
        $this->runEasyModulesCommand('make-model', 'Blog', 'Category', ['-m' => true]);

        $migrationFiles = glob($this->testBasePath('Blog/Database/Migrations/*_create_categories_table.php'));
        $this->assertNotEmpty($migrationFiles, 'Migration file should be created with short flag');
    }

    /**
     * Test model with seeder generation
     */
    #[Test]
    public function it_can_generate_model_with_seeder(): void
    {
        $this->runEasyModulesCommand('make-model', 'Blog', 'Post', ['--seed' => true]);

        $this->assertFilenameExists('Blog/Infrastructure/Models/Post.php');
        $this->assertModuleComponentExists('Blog', 'Database/Seeders', 'PostSeeder', [
            'use Illuminate\Database\Seeder;',
            'class PostSeeder extends Seeder',
            'public function run()',
        ]);
    }

    /**
     * Test seeder generation with short flag
     */
    #[Test]
    public function it_can_generate_model_with_seeder_short_flag(): void
    {
        $this->runEasyModulesCommand('make-model', 'Blog', 'Tag', ['-s' => true]);

        $this->assertFilenameExists('Blog/Database/Seeders/TagSeeder.php');
    }

    /**
     * Test namespace correctness in generated model files
     */
    #[Test]
    public function it_generates_correct_namespaces_in_model_files(): void
    {
        $this->runEasyModulesCommand('make-model', 'Blog', 'Post', ['--factory' => true]);

        // Model namespace should be correct
        $this->assertFileContains([
            'namespace App\\Modules\\Blog\\Infrastructure\\Models;',
        ], 'Blog/Infrastructure/Models/Post.php');

        // Should NOT contain duplicated namespace segments
        $this->assertFileNotContains([
            'namespace App\\Modules\\Blog\\Infrastructure\\Models\\App\\Modules\\Blog\\Infrastructure\\Models',
            'namespace App\\Modules\\Blog\\Infrastructure\\Models\\Infrastructure\\Models;',
            'namespace App\\Modules\\Blog\\Blog\\Infrastructure\\Models;',
        ], 'Blog/Infrastructure/Models/Post.php');

        // Factory namespace should be correct
        $this->assertFileContains([
            'namespace App\\Modules\\Blog\\Database\\Factories;',
        ], 'Blog/Database/Factories/PostFactory.php');

        // Factory should NOT contain duplicated namespace segments
        $this->assertFileNotContains([
            'namespace App\\Modules\\Blog\\Database\\Factories\\Database\\Factories;',
        ], 'Blog/Database/Factories/PostFactory.php');
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // NIVEAU 3: DOUBLE PATH PREVENTION TESTS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Test prevention of double path and namespace duplication
     *
     * This test ensures that neither file paths nor namespaces are duplicated
     * when generating models and their associated files within the module structure.
     */
    #[Test]
    public function it_prevents_double_path_and_namespace_duplication(): void
    {
        $this->runEasyModulesCommand('make-model', 'Blog', 'Post', ['--factory' => true, '--migration' => true]);

        // ✅ File path verification
        $this->assertFilenameExists('Blog/Infrastructure/Models/Post.php');
        $this->assertFilenameNotExists('Blog/Infrastructure/Models/Infrastructure/Models/Post.php');

        // ✅ Factory path verification
        $this->assertFilenameExists('Blog/Database/Factories/PostFactory.php');
        $this->assertFilenameNotExists('Blog/Database/Factories/Database/Factories/PostFactory.php');

        // ✅ Migration path verification
        $migrationFiles = glob($this->testBasePath('Blog/Database/Migrations/*_create_posts_table.php'));
        $this->assertNotEmpty($migrationFiles, 'Migration should exist');

        $duplicateMigrationFiles = glob($this->testBasePath('Blog/Database/Migrations/Database/Migrations/*_create_posts_table.php'));
        $this->assertEmpty($duplicateMigrationFiles, 'Should not create migration in duplicate path');

        // ✅ Namespace duplication prevention
        $this->assertFileNotContains([
            'App\\Modules\\Blog\\Infrastructure\\Models\\Infrastructure\\Models',
            'App\\Modules\\Blog\\Blog\\Infrastructure\\Models',
            'App\\Modules\\Blog\\Database\\Factories\\Database\\Factories',
            'Infrastructure\\Models\\Infrastructure\\Models',
        ], 'Blog/Infrastructure/Models/Post.php');
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // NIVEAU 2: CUSTOM CONFIGURATION TESTS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Test model with controller generation
     */
    #[Test]
    public function it_can_generate_model_with_controller(): void
    {
        $this->runEasyModulesCommand('make-model', 'Blog', 'Post', ['--controller' => true]);

        $this->assertFilenameExists('Blog/Infrastructure/Models/Post.php');
        $this->assertModuleComponentExists('Blog', 'Presentation/Http/Controllers', 'PostController', [
            'use Illuminate\Http\Request;',
            'class PostController',
        ]);
    }

    /**
     * Test model with resource controller generation
     */
    #[Test]
    public function it_can_generate_model_with_resource_controller(): void
    {
        // Clean slate - use unique name to avoid conflicts
        $this->artisan('easymodules:make-model', [
            'module'     => 'Blog',
            'name'       => 'ResourcePost',
            '--resource' => true,
        ])
            ->expectsConfirmation('A App\Modules\Blog\Infrastructure\Models\ResourcePost model does not exist. Do you want to generate it?', 'yes')
            ->assertExitCode(0);

        $this->assertModuleComponentExists('Blog', 'Presentation/Http/Controllers', 'ResourcePostController', [
            'class ResourcePostController',
            'public function index()',
            'public function create()',
            'public function store(Request $request)',
            'public function show(ResourcePost $resourcePost)',
            'public function edit(ResourcePost $resourcePost)',
            'public function update(Request $request, ResourcePost $resourcePost)',
            'public function destroy(ResourcePost $resourcePost)',
        ]);
    }

    /**
     * Test model with API controller generation
     */
    #[Test]
    public function it_can_generate_model_with_api_controller(): void
    {
        // Clean slate - use unique name to avoid conflicts
        $this->artisan('easymodules:make-model', [
            'module' => 'Blog',
            'name'   => 'ApiPost',
            '--api'  => true,
        ])
            ->expectsConfirmation('A App\Modules\Blog\Infrastructure\Models\ApiPost model does not exist. Do you want to generate it?', 'yes')
            ->assertExitCode(0);

        $this->assertModuleComponentExists('Blog', 'Presentation/Http/Controllers', 'ApiPostController', [
            'public function index()',
            'public function store(Request $request)',
            'public function show(ApiPost $apiPost)',
            'public function update(Request $request, ApiPost $apiPost)',
            'public function destroy(ApiPost $apiPost)',
        ]);

        // Should NOT contain web-only methods
        $this->assertFileNotContains([
            'public function create()',
            'public function edit(',
        ], 'Blog/Presentation/Http/Controllers/ApiPostController.php');
    }

    /**
     * Test controller generation with short flag
     */
    #[Test]
    public function it_can_generate_model_with_controller_short_flag(): void
    {
        $this->runEasyModulesCommand('make-model', 'Blog', 'Comment', ['-c' => true]);

        $this->assertFilenameExists('Blog/Presentation/Http/Controllers/CommentController.php');
    }

    /**
     * Test model with form requests generation
     */
    #[Test]
    public function it_can_generate_model_with_requests(): void
    {
        $this->runEasyModulesCommand('make-model', 'Blog', 'Post', ['--requests' => true]);

        // Check Store request
        $this->assertModuleComponentExists('Blog', 'Presentation/Http/Requests', 'StorePostRequest', [
            'use Illuminate\Foundation\Http\FormRequest;',
            'class StorePostRequest extends FormRequest',
        ]);

        // Check Update request
        $this->assertModuleComponentExists('Blog', 'Presentation/Http/Requests', 'UpdatePostRequest', [
            'use Illuminate\Foundation\Http\FormRequest;',
            'class UpdatePostRequest extends FormRequest',
        ]);
    }

    /**
     * Test model with policy generation
     */
    #[Test]
    public function it_can_generate_model_with_policy(): void
    {
        $this->runEasyModulesCommand('make-model', 'Blog', 'Post', ['--policy' => true]);

        $this->assertModuleComponentExists('Blog', 'Infrastructure/Policies', 'PostPolicy', [
            'use Illuminate\\Foundation\\Auth\\User;',
            'class PostPolicy',
            'public function viewAny(User $user)',
            'public function view(User $user, Post $post)',
            'public function create(User $user)',
            'public function update(User $user, Post $post)',
            'public function delete(User $user, Post $post)',
        ]);

        // Check policy does NOT contain double namespace in use statement
        $this->assertFileNotContains([
            'use App\\Modules\\Blog\\Infrastructure\\Models\\App\\Modules\\Blog\\Infrastructure\\Models\\Post;',
        ], 'Blog/Infrastructure/Policies/PostPolicy.php');
    }

    /**
     * Test model generation with --all option
     */
    #[Test]
    public function it_can_generate_model_with_all_option(): void
    {
        // Clean slate - use unique name to avoid conflicts
        $this->artisan('easymodules:make-model', [
            'module' => 'Blog',
            'name'   => 'AllPost',
            '--all'  => true,
        ])
            ->expectsConfirmation('A App\Modules\Blog\Infrastructure\Models\AllPost model does not exist. Do you want to generate it?', 'yes')
            ->assertExitCode(0);

        // Check model exists
        $this->assertFilenameExists('Blog/Infrastructure/Models/AllPost.php');

        // Check factory
        $this->assertFilenameExists('Blog/Database/Factories/AllPostFactory.php');

        // Check migration
        $migrationFiles = glob($this->testBasePath('Blog/Database/Migrations/*_create_all_posts_table.php'));
        $this->assertNotEmpty($migrationFiles, 'Migration should be created with --all');

        // Check seeder
        $this->assertFilenameExists('Blog/Database/Seeders/AllPostSeeder.php');

        // Check controller
        $this->assertFilenameExists('Blog/Presentation/Http/Controllers/AllPostController.php');

        // Check policy
        $this->assertFilenameExists('Blog/Infrastructure/Policies/AllPostPolicy.php');

        // Check requests
        $this->assertFilenameExists('Blog/Presentation/Http/Requests/StoreAllPostRequest.php');
        $this->assertFilenameExists('Blog/Presentation/Http/Requests/UpdateAllPostRequest.php');
    }

    /**
     * Test model generation with custom module configuration
     */
    #[Test]
    public function it_handles_custom_module_configurations(): void
    {
        // Test with custom base namespace
        $this->app['config']->set('easymodules.base_namespace', 'Custom\\Modules');
        $this->app['config']->set('easymodules.paths.model', 'Domain/Entities');
        $this->app['config']->set('easymodules.paths.factory', 'Infrastructure/Factories');

        $this->runEasyModulesCommand('make-model', 'Shop', 'Product', ['--factory' => true]);

        // Check custom paths are used
        $this->assertFilenameExists('Shop/Domain/Entities/Product.php');
        $this->assertFilenameExists('Shop/Infrastructure/Factories/ProductFactory.php');

        // Check custom namespaces
        $this->assertFileContains([
            'namespace Custom\\Modules\\Shop\\Domain\\Entities;',
        ], 'Shop/Domain/Entities/Product.php');

        $this->assertFileContains([
            'namespace Custom\\Modules\\Shop\\Infrastructure\\Factories;',
            'class ProductFactory extends Factory',
        ], 'Shop/Infrastructure/Factories/ProductFactory.php');

        // Check factory does NOT contain double namespace
        $this->assertFileNotContains([
            'use Custom\\Modules\\Shop\\Domain\\Entities\\Custom\\Modules\\Shop\\Domain\\Entities\\Product;',
        ], 'Shop/Infrastructure/Factories/ProductFactory.php');
    }

    /**
     * Test model generation with different module structures
     */
    #[Test]
    public function it_handles_different_module_structures(): void
    {
        $testConfigs = [
            ['namespace' => 'App\\Components', 'modelPath' => 'Models', 'factoryPath' => 'Factories'],
            ['namespace' => 'Custom\\App\\Modules', 'modelPath' => 'Domain/Entities', 'factoryPath' => 'Data/Factories'],
        ];

        foreach ($testConfigs as $index => $config) {
            $this->app['config']->set('easymodules.base_namespace', $config['namespace']);
            $this->app['config']->set('easymodules.paths.model', $config['modelPath']);
            $this->app['config']->set('easymodules.paths.factory', $config['factoryPath']);

            $modelName = "Test{$index}Model";

            $this->runEasyModulesCommand('make-model', 'Test', $modelName, ['--factory' => true]);

            $expectedModelPath   = "Test/{$config['modelPath']}/{$modelName}.php";
            $expectedFactoryPath = "Test/{$config['factoryPath']}/{$modelName}Factory.php";

            $this->assertFilenameExists($expectedModelPath);
            $this->assertFilenameExists($expectedFactoryPath);

            $expectedModelNamespace   = str_replace('/', '\\', "{$config['namespace']}\\Test\\{$config['modelPath']}");
            $expectedFactoryNamespace = str_replace('/', '\\', "{$config['namespace']}\\Test\\{$config['factoryPath']}");

            $this->assertFileContains([
                "namespace {$expectedModelNamespace};",
                "class {$modelName}",
            ], $expectedModelPath);

            $this->assertFileContains([
                "namespace {$expectedFactoryNamespace};",
                "class {$modelName}Factory",
            ], $expectedFactoryPath);

            // Should not contain duplicated path segments
            $pathSegments = explode('/', $config['modelPath']);

            $duplicatedPatterns = [];
            foreach ($pathSegments as $segment) {
                $duplicatedPatterns[] = "{$expectedModelNamespace}\\{$segment}\\{$segment}";
            }

            $this->assertFileNotContains($duplicatedPatterns, $expectedModelPath);
        }
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // NIVEAU 1: EDGE CASE & INTEGRATION TESTS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Test model generation with nested names
     */
    #[Test]
    public function it_handles_nested_model_names(): void
    {
        $this->runEasyModulesCommand('make-model', 'Blog', 'Category/Post', ['--factory' => true]);

        // Should create nested structure
        $this->assertFilenameExists('Blog/Infrastructure/Models/Category/Post.php');
        $this->assertFilenameExists('Blog/Database/Factories/Category/PostFactory.php');

        // Check nested namespaces
        $this->assertFileContains([
            'namespace App\\Modules\\Blog\\Infrastructure\\Models\\Category;',
        ], 'Blog/Infrastructure/Models/Category/Post.php');
    }

    /**
     * Test model generation with complex combinations
     */
    #[Test]
    public function it_handles_complex_option_combinations(): void
    {
        // Clean slate - use unique name to avoid conflicts
        $this->artisan('easymodules:make-model', [
            'module'       => 'Blog',
            'name'         => 'ComplexPost',
            '--factory'    => true,
            '--migration'  => true,
            '--controller' => true,
            '--requests'   => true,
            '--api'        => true,
        ])
            ->expectsConfirmation('A App\Modules\Blog\Infrastructure\Models\ComplexPost model does not exist. Do you want to generate it?', 'yes')
            ->assertExitCode(0);

        // All files should exist
        $this->assertFilenameExists('Blog/Infrastructure/Models/ComplexPost.php');
        $this->assertFilenameExists('Blog/Database/Factories/ComplexPostFactory.php');
        $this->assertFilenameExists('Blog/Presentation/Http/Controllers/ComplexPostController.php');
        $this->assertFilenameExists('Blog/Presentation/Http/Requests/StoreComplexPostRequest.php');
        $this->assertFilenameExists('Blog/Presentation/Http/Requests/UpdateComplexPostRequest.php');

        // Migration should exist
        $migrationFiles = glob($this->testBasePath('Blog/Database/Migrations/*_create_complex_posts_table.php'));
        $this->assertNotEmpty($migrationFiles);

        // Controller should be API type (no create/edit methods)
        $this->assertFileNotContains([
            'public function create()',
            'public function edit(',
        ], 'Blog/Presentation/Http/Controllers/ComplexPostController.php');
    }

    /**
     * Test model works with various module structures
     */
    #[Test]
    public function it_works_with_various_module_structures(): void
    {
        // Test multiple modules
        $modules = ['Blog', 'Shop', 'Admin'];

        foreach ($modules as $module) {
            $this->runEasyModulesCommand('make-model', $module, 'TestModel', ['--factory' => true]);

            $this->assertFilenameExists("{$module}/Infrastructure/Models/TestModel.php");
            $this->assertFilenameExists("{$module}/Database/Factories/TestModelFactory.php");

            $this->assertFileContains([
                "namespace App\\Modules\\{$module}\\Infrastructure\\Models;",
                'class TestModel extends Model',
            ], "{$module}/Infrastructure/Models/TestModel.php");
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
        $this->app['config']->set('easymodules.suffixes.model', 'Model');

        // Test suffix addition when missing
        $this->runEasyModulesCommand('make-model', 'Blog', 'User');

        $this->assertFilenameExists('Blog/Infrastructure/Models/UserModel.php');
        $this->assertFileContains([
            'class UserModel extends Model',
        ], 'Blog/Infrastructure/Models/UserModel.php');

        // Test suffix not duplicated when already present
        $this->runEasyModulesCommand('make-model', 'Blog', 'PostModel');

        $this->assertFilenameExists('Blog/Infrastructure/Models/PostModel.php');
        $this->assertFileContains([
            'class PostModel extends Model',
        ], 'Blog/Infrastructure/Models/PostModel.php');

        // Should NOT create double suffix
        $this->assertFilenameNotExists('Blog/Infrastructure/Models/PostModelModel.php');
    }

    /**
     * Test all Laravel model options work correctly
     */
    #[Test]
    public function it_supports_all_laravel_model_options(): void
    {
        // Test individual options work
        $options = [
            ['--factory' => true],
            ['--migration' => true],
            ['--seed' => true],
            ['--controller' => true],
            ['--policy' => true],
            ['--requests' => true],
            ['--pivot' => true],
            ['--morph-pivot' => true],
        ];

        foreach ($options as $index => $option) {
            $modelName = "Option{$index}Model";
            $this->runEasyModulesCommand('make-model', 'Test', $modelName, $option);

            // All should create the base model
            $this->assertFilenameExists("Test/Infrastructure/Models/{$modelName}.php");
        }
    }
}
