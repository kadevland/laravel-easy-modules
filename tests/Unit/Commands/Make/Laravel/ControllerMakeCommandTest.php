<?php

declare(strict_types=1);

namespace Kadevland\EasyModules\Tests\Unit\Commands\Make\Laravel;

use Kadevland\EasyModules\Tests\Support\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Test suite for ControllerMakeCommand
 *
 * This command extends Laravel's base ControllerMakeCommand to generate
 * controllers within the modular structure, supporting ALL Laravel options:
 * --api, --type, --force, --invokable, --model, --parent, --resource,
 * --requests, --singleton, --creatable, --test, --pest
 */
class ControllerMakeCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->setComponentPath('controller', 'Presentation/Http/Controllers');
        $this->setComponentPath('request', 'Presentation/Http/Requests');
        $this->setComponentPath('model', 'Infrastructure/Models');

        // Set app environment to testing to disable prompts
        $this->app['config']->set('app.env', 'testing');
    }

    protected function tearDown(): void
    {
        // Enhanced cleanup - remove all test files
        $testPaths = [
            'Blog/Presentation/Http/Controllers',
            'Blog/Presentation/Http/Requests',
            'Blog/Infrastructure/Models',
            'Shop/Presentation/Http/Controllers',
            'Shop/Presentation/Http/Requests',
            'Shop/Domain/Entities',
            'Test/Presentation/Http/Controllers',
            'Test/Presentation/Http/Requests',
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
    // NIVEAU 5: BASIC CONTROLLER GENERATION TESTS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Test basic controller file generation
     */
    #[Test]
    public function it_can_generate_basic_controller_file(): void
    {
        $this->runEasyModulesCommand('make-controller', 'Blog', 'PostController');

        $this->assertModuleComponentExists('Blog', 'Presentation/Http/Controllers', 'PostController', [
            'use Illuminate\Http\Request;',
            'class PostController',
        ]);
    }

    /**
     * Test invokable controller generation
     */
    #[Test]
    public function it_can_generate_invokable_controller(): void
    {
        $this->runEasyModulesCommand('make-controller', 'Blog', 'InvokableController', ['--invokable' => true]);

        $this->assertModuleComponentExists('Blog', 'Presentation/Http/Controllers', 'InvokableController', [
            'use Illuminate\Http\Request;',
            'class InvokableController',
            'public function __invoke(Request $request)',
        ]);
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // NIVEAU 5: API CONTROLLER TESTS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Test API controller generation
     */
    #[Test]
    public function it_can_generate_api_controller_file(): void
    {
        $this->runEasyModulesCommand('make-controller', 'Blog', 'ApiController', ['--api' => true]);

        $this->assertModuleComponentExists('Blog', 'Presentation/Http/Controllers', 'ApiController', [
            'class ApiController',
            'public function index()',
            'public function store(Request $request)',
            'public function show(string $id)',
            'public function update(Request $request, string $id)',
            'public function destroy(string $id)',
        ]);

        // Should NOT contain web-only methods
        $this->assertFileNotContains([
            'public function create()',
            'public function edit(',
        ], 'Blog/Presentation/Http/Controllers/ApiController.php');
    }

    /**
     * Test API controller with model
     */
    #[Test]
    public function it_can_generate_api_controller_with_model(): void
    {
        $this->artisan('easymodules:make-controller', [
            'module'  => 'Blog',
            'name'    => 'PostApiController',
            '--api'   => true,
            '--model' => 'Post',
        ])
            ->expectsConfirmation('A App\Modules\Blog\Infrastructure\Models\Post model does not exist. Do you want to generate it?', 'no')
            ->assertExitCode(0);

        $this->assertModuleComponentExists('Blog', 'Presentation/Http/Controllers', 'PostApiController', [
            'class PostApiController',
            'public function show(Post $post)',
            'public function update(Request $request, Post $post)',
            'public function destroy(Post $post)',
        ]);

        // Should NOT contain double namespace
        $this->assertFileNotContains([
            'App\\Modules\\Blog\\Infrastructure\\Models\\App\\Modules\\Blog\\Infrastructure\\Models\\Post',
        ], 'Blog/Presentation/Http/Controllers/PostApiController.php');
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // NIVEAU 5: RESOURCE CONTROLLER TESTS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Test resource controller generation
     */
    #[Test]
    public function it_can_generate_resource_controller(): void
    {
        $this->runEasyModulesCommand('make-controller', 'Blog', 'ResourceController', ['--resource' => true]);

        $this->assertModuleComponentExists('Blog', 'Presentation/Http/Controllers', 'ResourceController', [
            'class ResourceController',
            'public function index()',
            'public function create()',
            'public function store(Request $request)',
            'public function show(string $id)',
            'public function edit(string $id)',
            'public function update(Request $request, string $id)',
            'public function destroy(string $id)',
        ]);
    }

    /**
     * Test resource controller with model
     */
    #[Test]
    public function it_can_generate_resource_controller_with_model(): void
    {
        $this->artisan('easymodules:make-controller', [
            'module'     => 'Blog',
            'name'       => 'PostResourceController',
            '--resource' => true,
            '--model'    => 'Post',
        ])
            ->expectsConfirmation('A App\Modules\Blog\Infrastructure\Models\Post model does not exist. Do you want to generate it?', 'no')
            ->assertExitCode(0);

        $this->assertModuleComponentExists('Blog', 'Presentation/Http/Controllers', 'PostResourceController', [
            'class PostResourceController',
            'public function show(Post $post)',
            'public function edit(Post $post)',
            'public function update(Request $request, Post $post)',
            'public function destroy(Post $post)',
        ]);

        // Should reference the module model correctly
        $this->assertFileContains([
            'use App\\Modules\\Blog\\Infrastructure\\Models\\Post;',
        ], 'Blog/Presentation/Http/Controllers/PostResourceController.php');
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // NIVEAU 5: FORM REQUESTS GENERATION TESTS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Test controller with form requests generation
     */
    #[Test]
    public function it_can_generate_controller_with_requests(): void
    {
        $this->artisan('easymodules:make-controller', [
            'module'     => 'Blog',
            'name'       => 'RequestController',
            '--model'    => 'Post',
            '--requests' => true,
        ])
            ->expectsConfirmation('A App\Modules\Blog\Infrastructure\Models\Post model does not exist. Do you want to generate it?', 'no')
            ->assertExitCode(0);

        // Controller should be generated
        $this->assertFilenameExists('Blog/Presentation/Http/Controllers/RequestController.php');

        // Form requests should be generated automatically (named after model)
        $this->assertFilenameExists('Blog/Presentation/Http/Requests/StorePostRequest.php');
        $this->assertFilenameExists('Blog/Presentation/Http/Requests/UpdatePostRequest.php');

        // Check requests have correct structure
        $this->assertFileContains([
            'namespace App\\Modules\\Blog\\Presentation\\Http\\Requests;',
            'use Illuminate\\Foundation\\Http\\FormRequest;',
            'class StorePostRequest extends FormRequest',
        ], 'Blog/Presentation/Http/Requests/StorePostRequest.php');

        $this->assertFileContains([
            'namespace App\\Modules\\Blog\\Presentation\\Http\\Requests;',
            'use Illuminate\\Foundation\\Http\\FormRequest;',
            'class UpdatePostRequest extends FormRequest',
        ], 'Blog/Presentation/Http/Requests/UpdatePostRequest.php');
    }

    /**
     * Test API controller with requests
     */
    #[Test]
    public function it_can_generate_api_controller_with_requests(): void
    {
        $this->artisan('easymodules:make-controller', [
            'module'     => 'Blog',
            'name'       => 'ApiRequestController',
            '--api'      => true,
            '--model'    => 'Post',
            '--requests' => true,
        ])
            ->expectsConfirmation('A App\Modules\Blog\Infrastructure\Models\Post model does not exist. Do you want to generate it?', 'no')
            ->assertExitCode(0);

        // Controller and requests should be generated
        $this->assertFilenameExists('Blog/Presentation/Http/Controllers/ApiRequestController.php');
        $this->assertFilenameExists('Blog/Presentation/Http/Requests/StorePostRequest.php');
        $this->assertFilenameExists('Blog/Presentation/Http/Requests/UpdatePostRequest.php');
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // NIVEAU 4: NAMESPACE CORRECTNESS TESTS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Test controller namespace generation is correct
     */
    #[Test]
    public function it_generates_correct_controller_namespace(): void
    {
        $this->runEasyModulesCommand('make-controller', 'Blog', 'NamespaceController');

        // Controller should have correct namespace
        $this->assertFileContains([
            'namespace App\\Modules\\Blog\\Presentation\\Http\\Controllers;',
        ], 'Blog/Presentation/Http/Controllers/NamespaceController.php');

        // Should NOT contain duplicated namespace segments
        $this->assertFileNotContains([
            'namespace App\\Modules\\Blog\\Presentation\\Http\\Controllers\\Presentation\\Http\\Controllers;',
            'namespace App\\Modules\\Blog\\Blog\\Presentation\\Http\\Controllers;',
        ], 'Blog/Presentation/Http/Controllers/NamespaceController.php');
    }

    /**
     * Test model reference in controller is correct
     */
    #[Test]
    public function it_generates_correct_model_references(): void
    {
        $this->artisan('easymodules:make-controller', [
            'module'  => 'Blog',
            'name'    => 'ModelController',
            '--model' => 'Post',
        ])
            ->expectsConfirmation('A App\Modules\Blog\Infrastructure\Models\Post model does not exist. Do you want to generate it?', 'no')
            ->assertExitCode(0);

        // Should contain correct model reference
        $this->assertFileContains([
            'use App\\Modules\\Blog\\Infrastructure\\Models\\Post;',
        ], 'Blog/Presentation/Http/Controllers/ModelController.php');

        // Should NOT contain double namespace in any model references
        $this->assertFileNotContains([
            'App\\Modules\\Blog\\Infrastructure\\Models\\App\\Modules\\Blog\\Infrastructure\\Models\\Post',
        ], 'Blog/Presentation/Http/Controllers/ModelController.php');
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // NIVEAU 3: DOUBLE PATH PREVENTION TESTS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Test prevention of double path and namespace duplication
     *
     * This test ensures that neither file paths nor namespaces are duplicated
     * when generating controllers within the module structure.
     */
    #[Test]
    public function it_prevents_double_path_and_namespace_duplication(): void
    {
        $this->artisan('easymodules:make-controller', [
            'module'     => 'Blog',
            'name'       => 'PathController',
            '--model'    => 'Post',
            '--requests' => true,
        ])
            ->expectsConfirmation('A App\Modules\Blog\Infrastructure\Models\Post model does not exist. Do you want to generate it?', 'no')
            ->assertExitCode(0);

        // ✅ File path verification
        $this->assertFilenameExists('Blog/Presentation/Http/Controllers/PathController.php');
        $this->assertFilenameNotExists('Blog/Presentation/Http/Controllers/Presentation/Http/Controllers/PathController.php');

        // ✅ Namespace duplication prevention
        $this->assertFileNotContains([
            'App\\Modules\\Blog\\Presentation\\Http\\Controllers\\Presentation\\Http\\Controllers',
            'App\\Modules\\Blog\\Blog\\Presentation\\Http\\Controllers',
            'Presentation\\Http\\Controllers\\Presentation\\Http\\Controllers',
        ], 'Blog/Presentation/Http/Controllers/PathController.php');

        // Requests should have correct path without duplication (named after model, not controller)
        $this->assertFilenameExists('Blog/Presentation/Http/Requests/StorePostRequest.php');
        $this->assertFilenameExists('Blog/Presentation/Http/Requests/UpdatePostRequest.php');
        $this->assertFilenameNotExists('Blog/Presentation/Http/Requests/Presentation/Http/Requests/StorePostRequest.php');

        // Namespaces should be correct without duplication
        $this->assertFileContains([
            'namespace App\\Modules\\Blog\\Presentation\\Http\\Requests;',
        ], 'Blog/Presentation/Http/Requests/StorePostRequest.php');
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // NIVEAU 2: CUSTOM CONFIGURATION TESTS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Test nested controller generation
     */
    #[Test]
    public function it_can_generate_nested_controllers(): void
    {
        $this->runEasyModulesCommand('make-controller', 'Blog', 'Api/PostController', ['--api' => true]);

        // Should create nested directory structure
        $this->assertFilenameExists('Blog/Presentation/Http/Controllers/Api/PostController.php');

        $this->assertFileContains([
            'namespace App\\Modules\\Blog\\Presentation\\Http\\Controllers\\Api;',
            'class PostController',
        ], 'Blog/Presentation/Http/Controllers/Api/PostController.php');
    }

    /**
     * Test deeply nested controller with requests
     */
    #[Test]
    public function it_handles_deeply_nested_controllers_with_requests(): void
    {
        $this->artisan('easymodules:make-controller', [
            'module'     => 'Blog',
            'name'       => 'Admin/Api/UserController',
            '--model'    => 'User',
            '--requests' => true,
        ])
            ->expectsConfirmation('A App\Modules\Blog\Infrastructure\Models\User model does not exist. Do you want to generate it?', 'no')
            ->assertExitCode(0);

        $this->assertFilenameExists('Blog/Presentation/Http/Controllers/Admin/Api/UserController.php');
        $this->assertFilenameExists('Blog/Presentation/Http/Requests/StoreUserRequest.php');
        $this->assertFilenameExists('Blog/Presentation/Http/Requests/UpdateUserRequest.php');

        $this->assertFileContains([
            'namespace App\\Modules\\Blog\\Presentation\\Http\\Controllers\\Admin\\Api;',
            'class UserController',
        ], 'Blog/Presentation/Http/Controllers/Admin/Api/UserController.php');

        // Requests should still be in the base requests directory
        $this->assertFileContains([
            'namespace App\\Modules\\Blog\\Presentation\\Http\\Requests;',
        ], 'Blog/Presentation/Http/Requests/StoreUserRequest.php');
    }

    /**
     * Test controller generation with custom module configuration
     */
    #[Test]
    public function it_handles_custom_module_configurations(): void
    {
        // Test with custom base namespace and paths
        $this->app['config']->set('easymodules.base_namespace', 'Custom\\Modules');
        $this->app['config']->set('easymodules.paths.controller', 'Http/Controllers');
        $this->app['config']->set('easymodules.paths.request', 'Http/Requests');
        $this->app['config']->set('easymodules.paths.model', 'Domain/Entities');

        $this->artisan('easymodules:make-controller', [
            'module'     => 'Shop',
            'name'       => 'ProductController',
            '--model'    => 'Product',
            '--requests' => true,
        ])
            ->expectsConfirmation('A Custom\Modules\Shop\Domain\Entities\Product model does not exist. Do you want to generate it?', 'no')
            ->assertExitCode(0);

        // Check custom paths are used
        $this->assertFilenameExists('Shop/Http/Controllers/ProductController.php');
        $this->assertFilenameExists('Shop/Http/Requests/StoreProductRequest.php');
        $this->assertFilenameExists('Shop/Http/Requests/UpdateProductRequest.php');

        // Check custom namespaces
        $this->assertFileContains([
            'namespace Custom\\Modules\\Shop\\Http\\Controllers;',
            'class ProductController',
        ], 'Shop/Http/Controllers/ProductController.php');

        $this->assertFileContains([
            'namespace Custom\\Modules\\Shop\\Http\\Requests;',
        ], 'Shop/Http/Requests/StoreProductRequest.php');

        // Should NOT contain double namespace with custom config
        $this->assertFileNotContains([
            'Custom\\Modules\\Shop\\Domain\\Entities\\Custom\\Modules\\Shop\\Domain\\Entities\\Product',
        ], 'Shop/Http/Controllers/ProductController.php');
    }

    /**
     * Test controller generation with different module structures
     */
    #[Test]
    public function it_handles_different_module_structures(): void
    {
        $testConfigs = [
            ['namespace' => 'App\\Components', 'controllerPath' => 'Controllers', 'requestPath' => 'Requests', 'modelPath' => 'Models'],
            ['namespace' => 'Modules', 'controllerPath' => 'Http/Controllers', 'requestPath' => 'Http/Requests', 'modelPath' => 'Entities'],
        ];

        foreach ($testConfigs as $index => $config) {
            $this->app['config']->set('easymodules.base_namespace', $config['namespace']);
            $this->app['config']->set('easymodules.paths.controller', $config['controllerPath']);
            $this->app['config']->set('easymodules.paths.request', $config['requestPath']);
            $this->app['config']->set('easymodules.paths.model', $config['modelPath']);

            $controllerName = "Test{$index}Controller";

            $this->runEasyModulesCommand('make-controller', 'Test', $controllerName);

            $expectedControllerPath = "Test/{$config['controllerPath']}/{$controllerName}.php";
            $this->assertFilenameExists($expectedControllerPath);

            $expectedControllerNamespace = str_replace('/', '\\', "{$config['namespace']}\\Test\\{$config['controllerPath']}");
            $this->assertFileContains([
                "namespace {$expectedControllerNamespace};",
                "class {$controllerName}",
            ], $expectedControllerPath);

            // Should not contain duplicated path segments
            $pathSegments = explode('/', $config['controllerPath']);

            $duplicatedPatterns = [];
            foreach ($pathSegments as $segment) {
                $duplicatedPatterns[] = "{$expectedControllerNamespace}\\{$segment}\\{$segment}";
            }

            $this->assertFileNotContains($duplicatedPatterns, $expectedControllerPath);
        }
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // NIVEAU 1: EDGE CASE & INTEGRATION TESTS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Test parent controller option
     */
    #[Test]
    public function it_can_generate_controller_with_parent(): void
    {
        $this->artisan('easymodules:make-controller', [
            'module'   => 'Blog',
            'name'     => 'ChildController',
            '--parent' => 'Post',
            '--model'  => 'Comment',
        ])
            ->expectsConfirmation('A App\Modules\Blog\Infrastructure\Models\Post model does not exist. Do you want to generate it?', 'yes')
            ->expectsConfirmation('A App\Modules\Blog\Infrastructure\Models\Comment model does not exist. Do you want to generate it?', 'no')
            ->assertExitCode(0);

        $this->assertFilenameExists('Blog/Presentation/Http/Controllers/ChildController.php');

        // Should reference both models
        $this->assertFileContains([
            'use App\\Modules\\Blog\\Infrastructure\\Models\\Post;',
            'use App\\Modules\\Blog\\Infrastructure\\Models\\Comment;',
        ], 'Blog/Presentation/Http/Controllers/ChildController.php');
    }

    /**
     * Test singleton controller generation
     */
    #[Test]
    public function it_can_generate_singleton_controller(): void
    {
        $this->artisan('easymodules:make-controller', [
            'module'      => 'Blog',
            'name'        => 'SingletonController',
            '--singleton' => true,
            '--model'     => 'Setting',
        ])
            ->expectsConfirmation('A App\Modules\Blog\Infrastructure\Models\Setting model does not exist. Do you want to generate it?', 'no')
            ->assertExitCode(0);

        $this->assertFilenameExists('Blog/Presentation/Http/Controllers/SingletonController.php');

        // Singleton controllers have different method signatures - Laravel génère avec model binding
        $this->assertFileContains([
            'public function show(Setting $setting)',
            'public function edit(Setting $setting)',
            'public function update(Request $request, Setting $setting)',
        ], 'Blog/Presentation/Http/Controllers/SingletonController.php');
    }

    /**
     * Test complex option combinations
     */
    #[Test]
    public function it_handles_complex_option_combinations(): void
    {
        $this->artisan('easymodules:make-controller', [
            'module'     => 'Blog',
            'name'       => 'ComplexController',
            '--api'      => true,
            '--model'    => 'Post',
            '--requests' => true,
        ])
            ->expectsConfirmation('A App\Modules\Blog\Infrastructure\Models\Post model does not exist. Do you want to generate it?', 'no')
            ->assertExitCode(0);

        // All files should exist
        $this->assertFilenameExists('Blog/Presentation/Http/Controllers/ComplexController.php');
        $this->assertFilenameExists('Blog/Presentation/Http/Requests/StorePostRequest.php');
        $this->assertFilenameExists('Blog/Presentation/Http/Requests/UpdatePostRequest.php');
    }

    /**
     * Test controller generation with complex names
     */
    #[Test]
    public function it_handles_complex_controller_names(): void
    {
        $complexCases = [
            'UserProfileController',
            'BlogPostController',
            'AdminDashboardController',
            'ApiAuthenticationController',
        ];

        foreach ($complexCases as $controllerName) {
            $this->runEasyModulesCommand('make-controller', 'Test', $controllerName);

            $this->assertFilenameExists("Test/Presentation/Http/Controllers/{$controllerName}.php");
            $this->assertFileContains([
                "class {$controllerName}",
            ], "Test/Presentation/Http/Controllers/{$controllerName}.php");
        }
    }

    /**
     * Test controller generation edge cases
     */
    #[Test]
    public function it_handles_edge_cases_gracefully(): void
    {
        // Test with single character names
        $this->runEasyModulesCommand('make-controller', 'Blog', 'A');

        $this->assertFilenameExists('Blog/Presentation/Http/Controllers/A.php');
        $this->assertFileContains([
            'class A',
        ], 'Blog/Presentation/Http/Controllers/A.php');
    }

    /**
     * Test controller works with various module structures
     */
    #[Test]
    public function it_works_with_various_module_structures(): void
    {
        // Test multiple modules
        $modules = ['Blog', 'Shop', 'Admin'];

        foreach ($modules as $module) {
            $this->artisan('easymodules:make-controller', [
                'module'     => $module,
                'name'       => 'TestController',
                '--model'    => 'Test',
                '--requests' => true,
            ])
                ->expectsConfirmation("A App\Modules\\{$module}\Infrastructure\Models\Test model does not exist. Do you want to generate it?", 'no')
                ->assertExitCode(0);

            $this->assertFilenameExists("{$module}/Presentation/Http/Controllers/TestController.php");
            $this->assertFilenameExists("{$module}/Presentation/Http/Requests/StoreTestRequest.php");
            $this->assertFilenameExists("{$module}/Presentation/Http/Requests/UpdateTestRequest.php");

            $this->assertFileContains([
                "namespace App\\Modules\\{$module}\\Presentation\\Http\\Controllers;",
                'class TestController',
            ], "{$module}/Presentation/Http/Controllers/TestController.php");
        }
    }

    /**
     * Test multiple controllers in same module
     */
    #[Test]
    public function it_handles_multiple_controllers_in_same_module(): void
    {
        $controllers = [
            ['name' => 'PostController', 'options' => ['--model' => 'Post']],
            ['name' => 'CommentController', 'options' => ['--model' => 'Comment']],
            ['name' => 'ApiController', 'options' => ['--api' => true]],
            ['name' => 'AdminController', 'options' => ['--resource' => true]],
        ];

        foreach ($controllers as $controller) {
            if (isset($controller['options']['--model'])) {
                $this->artisan('easymodules:make-controller', array_merge([
                    'module' => 'Blog',
                    'name'   => $controller['name'],
                ], $controller['options']))
                    ->expectsConfirmation("A App\Modules\Blog\Infrastructure\Models\\{$controller['options']['--model']} model does not exist. Do you want to generate it?", 'no')
                    ->assertExitCode(0);
            } else {
                $this->runEasyModulesCommand('make-controller', 'Blog', $controller['name'], $controller['options']);
            }

            $this->assertFilenameExists("Blog/Presentation/Http/Controllers/{$controller['name']}.php");
        }

        // Verify all files exist and have correct content
        foreach ($controllers as $controller) {
            $this->assertFileContains([
                "class {$controller['name']}",
            ], "Blog/Presentation/Http/Controllers/{$controller['name']}.php");
        }
    }

    /**
     * Test that generated requests have proper structure
     */
    #[Test]
    public function it_generates_proper_request_structure(): void
    {
        $this->artisan('easymodules:make-controller', [
            'module'     => 'Blog',
            'name'       => 'RequestStructureController',
            '--model'    => 'Post',
            '--requests' => true,
        ])
            ->expectsConfirmation('A App\Modules\Blog\Infrastructure\Models\Post model does not exist. Do you want to generate it?', 'no')
            ->assertExitCode(0);

        // Should contain proper FormRequest structure
        $this->assertFileContains([
            'class StorePostRequest extends FormRequest',
            'public function authorize()',
            'public function rules()',
        ], 'Blog/Presentation/Http/Requests/StorePostRequest.php');

        $this->assertFileContains([
            'class UpdatePostRequest extends FormRequest',
            'public function authorize()',
            'public function rules()',
        ], 'Blog/Presentation/Http/Requests/UpdatePostRequest.php');
    }

    /**
     * Test controller properly references generated requests
     */
    #[Test]
    public function it_properly_references_generated_requests_in_controller(): void
    {
        $this->artisan('easymodules:make-controller', [
            'module'     => 'Blog',
            'name'       => 'RequestReferenceController',
            '--model'    => 'Product',
            '--requests' => true,
        ])
            ->expectsConfirmation('A App\Modules\Blog\Infrastructure\Models\Product model does not exist. Do you want to generate it?', 'no')
            ->assertExitCode(0);

        // Should contain use statements for both requests
        $this->assertFileContains([
            'use App\\Modules\\Blog\\Presentation\\Http\\Requests\\StoreProductRequest;',
            'use App\\Modules\\Blog\\Presentation\\Http\\Requests\\UpdateProductRequest;',
        ], 'Blog/Presentation/Http/Controllers/RequestReferenceController.php');
    }
}
