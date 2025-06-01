<?php

declare(strict_types=1);

namespace Kadevland\EasyModules\Tests\Unit\Commands\Make\Laravel;

use Kadevland\EasyModules\Tests\Support\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Test suite for RequestMakeCommand
 *
 * This command extends Laravel's base RequestMakeCommand to generate
 * form request classes within the modular structure.
 */
class RequestMakeCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->setComponentPath('request', 'Presentation/Http/Requests');

        // Set app environment to testing to disable prompts
        $this->app['config']->set('app.env', 'testing');
    }

    protected function tearDown(): void
    {
        // Enhanced cleanup - remove all test files
        $testPaths = [
            'Blog/Presentation/Http/Requests',
            'Shop/Presentation/Http/Requests',
            'Test/Presentation/Http/Requests',
            'Custom/Domain/Requests',
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
    // NIVEAU 5: BASIC REQUEST GENERATION TESTS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Test basic request file generation
     */
    #[Test]
    public function it_can_generate_basic_request_file(): void
    {
        $this->runEasyModulesCommand('make-request', 'Blog', 'PostRequest');

        $this->assertModuleComponentExists('Blog', 'Presentation/Http/Requests', 'PostRequest', [
            'use Illuminate\Foundation\Http\FormRequest;',
            'class PostRequest extends FormRequest',
            'public function authorize()',
            'public function rules()',
        ]);
    }

    /**
     * Test request generation with different naming patterns
     */
    #[Test]
    public function it_can_generate_requests_with_different_names(): void
    {
        $requestNames = [
            'StorePostRequest',
            'UpdatePostRequest',
            'CreateUserRequest',
            'EditProfileRequest',
        ];

        foreach ($requestNames as $requestName) {
            $this->runEasyModulesCommand('make-request', 'Blog', $requestName);

            $this->assertFilenameExists("Blog/Presentation/Http/Requests/{$requestName}.php");
            $this->assertFileContains([
                'namespace App\\Modules\\Blog\\Presentation\\Http\\Requests;',
                'use Illuminate\\Foundation\\Http\\FormRequest;',
                "class {$requestName} extends FormRequest",
            ], "Blog/Presentation/Http/Requests/{$requestName}.php");
        }
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // NIVEAU 4: NAMESPACE CORRECTNESS TESTS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Test request namespace generation is correct
     */
    #[Test]
    public function it_generates_correct_request_namespace(): void
    {
        $this->runEasyModulesCommand('make-request', 'Blog', 'CommentRequest');

        $this->assertFileContains([
            'namespace App\\Modules\\Blog\\Presentation\\Http\\Requests;',
            'class CommentRequest extends FormRequest',
        ], 'Blog/Presentation/Http/Requests/CommentRequest.php');
    }

    /**
     * Test request methods are properly generated
     */
    #[Test]
    public function it_generates_correct_request_structure(): void
    {
        $this->runEasyModulesCommand('make-request', 'Blog', 'UserRequest');

        $this->assertFileContains([
            'public function authorize()',
            'public function rules()',
            'return false;',
            'return [',
        ], 'Blog/Presentation/Http/Requests/UserRequest.php');
    }

    /**
     * Test nested request generation
     */
    #[Test]
    public function it_can_generate_nested_requests(): void
    {
        $this->runEasyModulesCommand('make-request', 'Blog', 'Auth/LoginRequest');

        $this->assertFilenameExists('Blog/Presentation/Http/Requests/Auth/LoginRequest.php');
        $this->assertFileContains([
            'namespace App\\Modules\\Blog\\Presentation\\Http\\Requests\\Auth;',
            'class LoginRequest extends FormRequest',
        ], 'Blog/Presentation/Http/Requests/Auth/LoginRequest.php');
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // NIVEAU 3: DOUBLE PATH PREVENTION TESTS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Test prevention of double path and namespace duplication
     *
     * This test ensures that neither file paths nor namespaces are duplicated
     * when generating requests within the module structure.
     */
    #[Test]
    public function it_prevents_double_path_and_namespace_duplication(): void
    {
        $this->runEasyModulesCommand('make-request', 'Blog', 'PathTest');

        // ✅ File path verification
        $this->assertFilenameExists('Blog/Presentation/Http/Requests/PathTest.php');
        $this->assertFilenameNotExists('Blog/Presentation/Http/Requests/Presentation/Http/Requests/PathTest.php');

        // ✅ Namespace duplication prevention
        $this->assertFileNotContains([
            'App\\Modules\\Blog\\Presentation\\Http\\Requests\\Presentation\\Http\\Requests',
            'App\\Modules\\Blog\\Blog\\Presentation\\Http\\Requests',
            'Presentation\\Http\\Requests\\Presentation\\Http\\Requests',
        ], 'Blog/Presentation/Http/Requests/PathTest.php');

        // Test deeply nested structure as well
        $this->runEasyModulesCommand('make-request', 'Blog', 'Admin/User/UpdateProfileRequest');

        $this->assertFilenameExists('Blog/Presentation/Http/Requests/Admin/User/UpdateProfileRequest.php');
        $this->assertFileContains([
            'namespace App\\Modules\\Blog\\Presentation\\Http\\Requests\\Admin\\User;',
            'class UpdateProfileRequest extends FormRequest',
        ], 'Blog/Presentation/Http/Requests/Admin/User/UpdateProfileRequest.php');

        // Should not contain path duplication in deeply nested structure
        $this->assertFileNotContains([
            'Presentation\\Http\\Requests\\Admin\\User\\Presentation\\Http\\Requests\\Admin\\User',
        ], 'Blog/Presentation/Http/Requests/Admin/User/UpdateProfileRequest.php');
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // NIVEAU 2: CUSTOM CONFIGURATION TESTS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Test request generation with custom module configuration
     */
    #[Test]
    public function it_handles_custom_module_configurations(): void
    {
        // Test with custom base namespace and paths
        $this->app['config']->set('easymodules.base_namespace', 'Custom\\Modules');
        $this->app['config']->set('easymodules.paths.request', 'Http/Requests');

        $this->runEasyModulesCommand('make-request', 'Shop', 'ProductRequest');

        // Check custom paths are used
        $this->assertFilenameExists('Shop/Http/Requests/ProductRequest.php');

        // Check custom namespaces
        $this->assertFileContains([
            'namespace Custom\\Modules\\Shop\\Http\\Requests;',
            'class ProductRequest extends FormRequest',
        ], 'Shop/Http/Requests/ProductRequest.php');

        // Should NOT contain double namespace with custom config
        $this->assertFileNotContains([
            'Custom\\Modules\\Shop\\Http\\Requests\\Http\\Requests',
        ], 'Shop/Http/Requests/ProductRequest.php');
    }

    /**
     * Test request generation with different module structures
     */
    #[Test]
    public function it_handles_different_module_structures(): void
    {
        $testConfigs = [
            ['namespace' => 'App\\Components', 'requestPath' => 'Requests'],
            ['namespace' => 'Modules', 'requestPath' => 'Web/Requests'],
            ['namespace' => 'Custom\\App\\Modules', 'requestPath' => 'Api/Requests'],
        ];

        foreach ($testConfigs as $index => $config) {
            $this->app['config']->set('easymodules.base_namespace', $config['namespace']);
            $this->app['config']->set('easymodules.paths.request', $config['requestPath']);

            $requestName = "Test{$index}Request";

            $this->runEasyModulesCommand('make-request', 'Test', $requestName);

            $expectedRequestPath = "Test/{$config['requestPath']}/{$requestName}.php";
            $this->assertFilenameExists($expectedRequestPath);

            $expectedRequestNamespace = str_replace('/', '\\', "{$config['namespace']}\\Test\\{$config['requestPath']}");
            $this->assertFileContains([
                "namespace {$expectedRequestNamespace};",
                "class {$requestName} extends FormRequest",
            ], $expectedRequestPath);

            // Should not contain duplicated path segments
            $pathSegments = explode('/', $config['requestPath']);

            $duplicatedPatterns = [];
            foreach ($pathSegments as $segment) {
                $duplicatedPatterns[] = "{$expectedRequestNamespace}\\{$segment}\\{$segment}";
            }

            $this->assertFileNotContains($duplicatedPatterns, $expectedRequestPath);
        }
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // NIVEAU 1: EDGE CASE & INTEGRATION TESTS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Test common CRUD request patterns
     */
    #[Test]
    public function it_generates_common_crud_requests(): void
    {
        $crudRequests = [
            'StorePostRequest',
            'UpdatePostRequest',
            'CreateCategoryRequest',
            'EditUserRequest',
            'DeleteArticleRequest',
        ];

        foreach ($crudRequests as $requestName) {
            $this->runEasyModulesCommand('make-request', 'Blog', $requestName);

            $this->assertFilenameExists("Blog/Presentation/Http/Requests/{$requestName}.php");
            $this->assertFileContains([
                "class {$requestName} extends FormRequest",
                'public function authorize()',
                'public function rules()',
            ], "Blog/Presentation/Http/Requests/{$requestName}.php");
        }
    }

    /**
     * Test API request patterns
     */
    #[Test]
    public function it_generates_api_request_patterns(): void
    {
        $apiRequests = [
            'Api/CreateUserRequest',
            'Api/UpdateUserRequest',
            'Api/Auth/LoginRequest',
            'Api/Auth/RegisterRequest',
        ];

        foreach ($apiRequests as $requestPath) {
            $this->runEasyModulesCommand('make-request', 'Blog', $requestPath);

            $expectedFile = "Blog/Presentation/Http/Requests/{$requestPath}.php";
            $this->assertFilenameExists($expectedFile);

            $className     = basename($requestPath);
            $namespacePath = str_replace('/', '\\', dirname($requestPath));

            if ($namespacePath !== '.') {
                $this->assertFileContains([
                    "namespace App\\Modules\\Blog\\Presentation\\Http\\Requests\\{$namespacePath};",
                    "class {$className} extends FormRequest",
                ], $expectedFile);
            }
        }
    }

    /**
     * Test request generation with complex names
     */
    #[Test]
    public function it_handles_complex_request_names(): void
    {
        $complexCases = [
            'UserProfileUpdateRequest',
            'BlogPostPublishRequest',
            'AdminUserPermissionRequest',
            'PaymentMethodVerificationRequest',
        ];

        foreach ($complexCases as $requestName) {
            $this->runEasyModulesCommand('make-request', 'Test', $requestName);

            $this->assertFilenameExists("Test/Presentation/Http/Requests/{$requestName}.php");
            $this->assertFileContains([
                "class {$requestName} extends FormRequest",
                'namespace App\\Modules\\Test\\Presentation\\Http\\Requests;',
            ], "Test/Presentation/Http/Requests/{$requestName}.php");
        }
    }

    /**
     * Test request generation edge cases
     */
    #[Test]
    public function it_handles_edge_cases_gracefully(): void
    {
        // Test with single character names
        $this->runEasyModulesCommand('make-request', 'Blog', 'ARequest');

        $this->assertFilenameExists('Blog/Presentation/Http/Requests/ARequest.php');
        $this->assertFileContains([
            'class ARequest extends FormRequest',
        ], 'Blog/Presentation/Http/Requests/ARequest.php');

        // Test with numbers in names
        $this->runEasyModulesCommand('make-request', 'Blog', 'Api2Request');

        $this->assertFilenameExists('Blog/Presentation/Http/Requests/Api2Request.php');
        $this->assertFileContains([
            'class Api2Request extends FormRequest',
        ], 'Blog/Presentation/Http/Requests/Api2Request.php');
    }

    /**
     * Test request works with various module structures
     */
    #[Test]
    public function it_works_with_various_module_structures(): void
    {
        // Test multiple modules
        $modules = ['Blog', 'Shop', 'Admin'];

        foreach ($modules as $module) {
            $this->runEasyModulesCommand('make-request', $module, 'TestRequest');

            $this->assertFilenameExists("{$module}/Presentation/Http/Requests/TestRequest.php");
            $this->assertFileContains([
                "namespace App\\Modules\\{$module}\\Presentation\\Http\\Requests;",
                'class TestRequest extends FormRequest',
            ], "{$module}/Presentation/Http/Requests/TestRequest.php");
        }
    }

    /**
     * Test multiple requests in same module
     */
    #[Test]
    public function it_handles_multiple_requests_in_same_module(): void
    {
        $requests = [
            'StorePostRequest',
            'UpdatePostRequest',
            'DeletePostRequest',
            'Auth/LoginRequest',
            'Auth/RegisterRequest',
        ];

        foreach ($requests as $requestPath) {
            $this->runEasyModulesCommand('make-request', 'Blog', $requestPath);

            $expectedFile = "Blog/Presentation/Http/Requests/{$requestPath}.php";
            $this->assertFilenameExists($expectedFile);
        }

        // Verify all files exist and have correct content
        foreach ($requests as $requestPath) {
            $expectedFile = "Blog/Presentation/Http/Requests/{$requestPath}.php";
            $className    = basename($requestPath);
            $this->assertFileContains([
                "class {$className} extends FormRequest",
            ], $expectedFile);
        }
    }

    /**
     * Test that generated requests have proper validation structure
     */
    #[Test]
    public function it_generates_proper_validation_structure(): void
    {
        $this->runEasyModulesCommand('make-request', 'Blog', 'ValidationTestRequest');

        $this->assertFileContains([
            'public function authorize(): bool',
            'public function rules(): array',
            'return false;',
            'return [',
            '//',
        ], 'Blog/Presentation/Http/Requests/ValidationTestRequest.php');
    }
}
