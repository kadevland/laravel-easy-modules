<?php

declare(strict_types=1);

namespace Kadevland\EasyModules\Tests\Unit\Commands\Make\Laravel;

use Kadevland\EasyModules\Tests\Support\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Test suite for MiddlewareMakeCommand
 *
 * This command extends Laravel's base MiddlewareMakeCommand to generate
 * HTTP middleware classes within the modular structure.
 */
class MiddlewareMakeCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->setComponentPath('middleware', 'Presentation/Http/Middleware');

        // Set app environment to testing to disable prompts
        $this->app['config']->set('app.env', 'testing');
    }

    protected function tearDown(): void
    {
        // Enhanced cleanup - remove all test files
        $testPaths = [
            'Blog/Presentation/Http/Middleware',
            'Shop/Presentation/Http/Middleware',
            'Test/Presentation/Http/Middleware',
            'Custom/Security/Middleware',
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
    // NIVEAU 5: BASIC MIDDLEWARE GENERATION TESTS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Test basic middleware file generation
     */
    #[Test]
    public function it_can_generate_basic_middleware_file(): void
    {
        $this->runEasyModulesCommand('make-middleware', 'Blog', 'AuthMiddleware');

        $this->assertFileContains([
            'namespace App\\Modules\\Blog\\Presentation\\Http\\Middleware;',
            'use Closure;',
            'use Illuminate\\Http\\Request;',
            'class AuthMiddleware',
            'public function handle(Request $request, Closure $next)',
            'return $next($request);',
        ], 'Blog/Presentation/Http/Middleware/AuthMiddleware.php');
    }

    /**
     * Test middleware generation with different naming patterns
     */
    #[Test]
    public function it_can_generate_middlewares_with_different_names(): void
    {
        $middlewareNames = [
            'CheckRole',
            'ValidateApiKey',
            'CorsMiddleware',
            'RateLimitMiddleware',
        ];

        foreach ($middlewareNames as $middlewareName) {
            $this->runEasyModulesCommand('make-middleware', 'Blog', $middlewareName);

            $this->assertFilenameExists("Blog/Presentation/Http/Middleware/{$middlewareName}.php");
            $this->assertFileContains([
                'namespace App\\Modules\\Blog\\Presentation\\Http\\Middleware;',
                'use Closure;',
                'use Illuminate\\Http\\Request;',
                "class {$middlewareName}",
                'public function handle(Request $request, Closure $next)',
            ], "Blog/Presentation/Http/Middleware/{$middlewareName}.php");
        }
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // NIVEAU 4: NAMESPACE CORRECTNESS TESTS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Test middleware namespace generation is correct
     */
    #[Test]
    public function it_generates_correct_middleware_namespace(): void
    {
        $this->runEasyModulesCommand('make-middleware', 'Blog', 'AdminCheck');

        $this->assertFileContains([
            'namespace App\\Modules\\Blog\\Presentation\\Http\\Middleware;',
            'class AdminCheck',
        ], 'Blog/Presentation/Http/Middleware/AdminCheck.php');
    }

    /**
     * Test middleware structure is properly generated
     */
    #[Test]
    public function it_generates_correct_middleware_structure(): void
    {
        $this->runEasyModulesCommand('make-middleware', 'Blog', 'PermissionCheck');

        $this->assertFileContains([
            'use Closure;',
            'use Illuminate\\Http\\Request;',
            'public function handle(Request $request, Closure $next)',
            'return $next($request);',
            'Handle an incoming request',
        ], 'Blog/Presentation/Http/Middleware/PermissionCheck.php');
    }

    /**
     * Test nested middleware generation
     */
    #[Test]
    public function it_can_generate_nested_middlewares(): void
    {
        $this->runEasyModulesCommand('make-middleware', 'Blog', 'Auth/JwtMiddleware');

        $this->assertFilenameExists('Blog/Presentation/Http/Middleware/Auth/JwtMiddleware.php');
        $this->assertFileContains([
            'namespace App\\Modules\\Blog\\Presentation\\Http\\Middleware\\Auth;',
            'class JwtMiddleware',
        ], 'Blog/Presentation/Http/Middleware/Auth/JwtMiddleware.php');
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // NIVEAU 3: DOUBLE PATH PREVENTION TESTS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Test prevention of double path and namespace duplication
     *
     * This test ensures that neither file paths nor namespaces are duplicated
     * when generating middleware within the module structure.
     */
    #[Test]
    public function it_prevents_double_path_and_namespace_duplication(): void
    {
        $this->runEasyModulesCommand('make-middleware', 'Blog', 'SecurityMiddleware');

        // File path verification
        $this->assertFilenameExists('Blog/Presentation/Http/Middleware/SecurityMiddleware.php');
        $this->assertFilenameNotExists('Blog/Presentation/Http/Middleware/Presentation/Http/Middleware/SecurityMiddleware.php');

        // Namespace duplication prevention
        $this->assertFileNotContains([
            'App\\Modules\\Blog\\Presentation\\Http\\Middleware\\Presentation\\Http\\Middleware',
            'App\\Modules\\Blog\\Blog\\Presentation\\Http\\Middleware',
            'Presentation\\Http\\Middleware\\Presentation\\Http\\Middleware',
        ], 'Blog/Presentation/Http/Middleware/SecurityMiddleware.php');
    }

    /**
     * Test deeply nested middleware generation without duplication
     */
    #[Test]
    public function it_handles_deeply_nested_middlewares_without_duplication(): void
    {
        $this->runEasyModulesCommand('make-middleware', 'Blog', 'Api/Security/RateLimitMiddleware');

        $this->assertFilenameExists('Blog/Presentation/Http/Middleware/Api/Security/RateLimitMiddleware.php');
        $this->assertFileContains([
            'namespace App\\Modules\\Blog\\Presentation\\Http\\Middleware\\Api\\Security;',
            'class RateLimitMiddleware',
        ], 'Blog/Presentation/Http/Middleware/Api/Security/RateLimitMiddleware.php');

        // Should not contain path duplication in deeply nested structure
        $this->assertFileNotContains([
            'Presentation\\Http\\Middleware\\Api\\Security\\Presentation\\Http\\Middleware\\Api\\Security',
        ], 'Blog/Presentation/Http/Middleware/Api/Security/RateLimitMiddleware.php');
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // NIVEAU 2: CUSTOM CONFIGURATION TESTS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Test middleware generation with custom module configuration
     */
    #[Test]
    public function it_handles_custom_module_configurations(): void
    {
        // Test with custom base namespace and paths
        $this->app['config']->set('easymodules.base_namespace', 'Custom\\Modules');
        $this->app['config']->set('easymodules.paths.middleware', 'Http/Middleware');

        $this->runEasyModulesCommand('make-middleware', 'Shop', 'ProductMiddleware');

        // Check custom paths are used
        $this->assertFilenameExists('Shop/Http/Middleware/ProductMiddleware.php');

        // Check custom namespaces
        $this->assertFileContains([
            'namespace Custom\\Modules\\Shop\\Http\\Middleware;',
            'class ProductMiddleware',
        ], 'Shop/Http/Middleware/ProductMiddleware.php');

        // Should NOT contain double namespace with custom config
        $this->assertFileNotContains([
            'Custom\\Modules\\Shop\\Http\\Middleware\\Http\\Middleware',
        ], 'Shop/Http/Middleware/ProductMiddleware.php');
    }

    /**
     * Test middleware generation with different module structures
     */
    #[Test]
    public function it_handles_different_module_structures(): void
    {
        $testConfigs = [
            ['namespace' => 'App\\Components', 'middlewarePath' => 'Middleware'],
            ['namespace' => 'Modules', 'middlewarePath' => 'Web/Middleware'],
            ['namespace' => 'Custom\\App\\Modules', 'middlewarePath' => 'Api/Middleware'],
        ];

        foreach ($testConfigs as $index => $config) {
            $this->app['config']->set('easymodules.base_namespace', $config['namespace']);
            $this->app['config']->set('easymodules.paths.middleware', $config['middlewarePath']);

            $middlewareName = "Test{$index}Middleware";

            $this->runEasyModulesCommand('make-middleware', 'Test', $middlewareName);

            $expectedMiddlewarePath = "Test/{$config['middlewarePath']}/{$middlewareName}.php";
            $this->assertFilenameExists($expectedMiddlewarePath);

            $expectedMiddlewareNamespace = str_replace('/', '\\', "{$config['namespace']}\\Test\\{$config['middlewarePath']}");
            $this->assertFileContains([
                "namespace {$expectedMiddlewareNamespace};",
                "class {$middlewareName}",
            ], $expectedMiddlewarePath);

            // Should not contain duplicated path segments
            $pathSegments = explode('/', $config['middlewarePath']);


            $duplicatedPatterns = [];
            foreach ($pathSegments as $segment) {
                $duplicatedPatterns[] = "{$expectedMiddlewareNamespace}\\{$segment}\\{$segment}";
            }

            $this->assertFileNotContains($duplicatedPatterns, $expectedMiddlewarePath);
        }
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // NIVEAU 1: EDGE CASE & INTEGRATION TESTS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Test common middleware patterns
     */
    #[Test]
    public function it_generates_common_middleware_patterns(): void
    {
        $commonMiddleware = [
            'AuthenticateMiddleware',
            'CorsMiddleware',
            'RateLimitMiddleware',
            'ValidateJsonMiddleware',
            'CheckPermissionMiddleware',
        ];

        foreach ($commonMiddleware as $middlewareName) {
            $this->runEasyModulesCommand('make-middleware', 'Blog', $middlewareName);

            $this->assertFilenameExists("Blog/Presentation/Http/Middleware/{$middlewareName}.php");
            $this->assertFileContains([
                "class {$middlewareName}",
                'public function handle(Request $request, Closure $next)',
                'return $next($request);',
            ], "Blog/Presentation/Http/Middleware/{$middlewareName}.php");
        }
    }

    /**
     * Test API middleware patterns
     */
    #[Test]
    public function it_generates_api_middleware_patterns(): void
    {
        $apiMiddleware = [
            'Api/AuthMiddleware',
            'Api/RateLimit',
            'Api/Cors',
            'Api/ValidateApiKey',
        ];

        foreach ($apiMiddleware as $middlewarePath) {
            $this->runEasyModulesCommand('make-middleware', 'Blog', $middlewarePath);

            $expectedFile = "Blog/Presentation/Http/Middleware/{$middlewarePath}.php";
            $this->assertFilenameExists($expectedFile);

            $className     = basename($middlewarePath);
            $namespacePath = str_replace('/', '\\', dirname($middlewarePath));

            if ($namespacePath !== '.') {
                $this->assertFileContains([
                    "namespace App\\Modules\\Blog\\Presentation\\Http\\Middleware\\{$namespacePath};",
                    "class {$className}",
                ], $expectedFile);
            }
        }
    }

    /**
     * Test middleware generation with complex names
     */
    #[Test]
    public function it_handles_complex_middleware_names(): void
    {
        $complexCases = [
            'CheckUserPermissionMiddleware',
            'ValidateApiKeyAndRateLimitMiddleware',
            'CorsAndSecurityHeadersMiddleware',
            'JsonResponseFormatterMiddleware',
        ];

        foreach ($complexCases as $middlewareName) {
            $this->runEasyModulesCommand('make-middleware', 'Test', $middlewareName);

            $this->assertFilenameExists("Test/Presentation/Http/Middleware/{$middlewareName}.php");
            $this->assertFileContains([
                "class {$middlewareName}",
                'namespace App\\Modules\\Test\\Presentation\\Http\\Middleware;',
            ], "Test/Presentation/Http/Middleware/{$middlewareName}.php");
        }
    }

    /**
     * Test middleware generation edge cases
     */
    #[Test]
    public function it_handles_edge_cases_gracefully(): void
    {
        // Test with single character names
        $this->runEasyModulesCommand('make-middleware', 'Blog', 'A');

        $this->assertFilenameExists('Blog/Presentation/Http/Middleware/A.php');
        $this->assertFileContains([
            'class A',
        ], 'Blog/Presentation/Http/Middleware/A.php');

        // Test with numbers in names
        $this->runEasyModulesCommand('make-middleware', 'Blog', 'Auth2Factor');

        $this->assertFilenameExists('Blog/Presentation/Http/Middleware/Auth2Factor.php');
        $this->assertFileContains([
            'class Auth2Factor',
        ], 'Blog/Presentation/Http/Middleware/Auth2Factor.php');
    }

    /**
     * Test middleware works with various module structures
     */
    #[Test]
    public function it_works_with_various_module_structures(): void
    {
        // Test multiple modules
        $modules = ['Blog', 'Shop', 'Admin'];

        foreach ($modules as $module) {
            $this->runEasyModulesCommand('make-middleware', $module, 'TestMiddleware');

            $this->assertFilenameExists("{$module}/Presentation/Http/Middleware/TestMiddleware.php");
            $this->assertFileContains([
                "namespace App\\Modules\\{$module}\\Presentation\\Http\\Middleware;",
                'class TestMiddleware',
            ], "{$module}/Presentation/Http/Middleware/TestMiddleware.php");
        }
    }

    /**
     * Test multiple middleware in same module
     */
    #[Test]
    public function it_handles_multiple_middlewares_in_same_module(): void
    {
        $middleware = [
            'AuthMiddleware',
            'PermissionMiddleware',
            'RateLimitMiddleware',
            'Auth/JwtMiddleware',
            'Api/CorsMiddleware',
        ];

        foreach ($middleware as $middlewarePath) {
            $this->runEasyModulesCommand('make-middleware', 'Blog', $middlewarePath);

            $expectedFile = "Blog/Presentation/Http/Middleware/{$middlewarePath}.php";
            $this->assertFilenameExists($expectedFile);
        }

        // Verify all files exist and have correct content
        foreach ($middleware as $middlewarePath) {
            $expectedFile = "Blog/Presentation/Http/Middleware/{$middlewarePath}.php";
            $className    = basename($middlewarePath);
            $this->assertFileContains([
                "class {$className}",
            ], $expectedFile);
        }
    }

    /**
     * Test that generated middleware have proper structure
     */
    #[Test]
    public function it_generates_proper_middleware_structure(): void
    {
        $this->runEasyModulesCommand('make-middleware', 'Blog', 'StructureTestMiddleware');

        $this->assertFileContains([
            'public function handle(Request $request, Closure $next)',
            'use Closure;',
            'use Illuminate\\Http\\Request;',
            'return $next($request);',
            'Handle an incoming request',
            '@param',
        ], 'Blog/Presentation/Http/Middleware/StructureTestMiddleware.php');
    }

    /**
     * Test middleware with terminable capabilities structure
     */
    #[Test]
    public function it_can_generate_terminable_middleware_structure(): void
    {
        $this->runEasyModulesCommand('make-middleware', 'Blog', 'TerminableMiddleware');

        $this->assertFileContains([
            'class TerminableMiddleware',
            'public function handle(Request $request, Closure $next)',
        ], 'Blog/Presentation/Http/Middleware/TerminableMiddleware.php');

        // Note: Laravel's base MiddlewareMakeCommand doesn't have --terminable option
        // but the structure allows manual addition of terminate method if needed
    }
}
