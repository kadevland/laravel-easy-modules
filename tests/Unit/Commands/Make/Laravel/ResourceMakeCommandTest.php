<?php

declare(strict_types=1);

namespace Kadevland\EasyModules\Tests\Unit\Commands\Make\Laravel;

use Kadevland\EasyModules\Tests\Support\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Test suite for ResourceMakeCommand
 *
 * This command extends Laravel's base ResourceMakeCommand to generate
 * API resource classes within the modular structure, supporting both
 * single resources and resource collections.
 */
class ResourceMakeCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->setComponentPath('resource', 'Presentation/Http/Resources');

        // Set app environment to testing to disable prompts
        $this->app['config']->set('app.env', 'testing');
    }

    protected function tearDown(): void
    {
        // Enhanced cleanup - remove all test files
        $testPaths = [
            'Blog/Presentation/Http/Resources',
            'Shop/Presentation/Http/Resources',
            'Test/Presentation/Http/Resources',
            'Custom/Domain/Resources',
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
    // NIVEAU 5: BASIC RESOURCE GENERATION TESTS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Test basic resource file generation
     */
    #[Test]
    public function it_can_generate_basic_resource_file(): void
    {
        $this->runEasyModulesCommand('make-resource', 'Blog', 'PostResource');

        $this->assertModuleComponentExists('Blog', 'Presentation/Http/Resources', 'PostResource', [
            'use Illuminate\Http\Request;',
            'use Illuminate\Http\Resources\Json\JsonResource;',
            'class PostResource extends JsonResource',
            'public function toArray(Request $request): array',
        ]);
    }

    /**
     * Test resource collection generation
     */
    #[Test]
    public function it_can_generate_resource_collection(): void
    {
        $this->runEasyModulesCommand('make-resource', 'Blog', 'PostCollection', ['--collection' => true]);

        $this->assertModuleComponentExists('Blog', 'Presentation/Http/Resources', 'PostCollection', [
            'use Illuminate\Http\Request;',
            'use Illuminate\Http\Resources\Json\ResourceCollection;',
            'class PostCollection extends ResourceCollection',
            'public function toArray(Request $request): array',
        ]);
    }

    /**
     * Test resource generation with different naming patterns
     */
    #[Test]
    public function it_can_generate_resources_with_different_names(): void
    {
        $resourceNames = [
            'UserResource',
            'PostResource',
            'CommentResource',
            'CategoryResource',
        ];

        foreach ($resourceNames as $resourceName) {
            $this->runEasyModulesCommand('make-resource', 'Blog', $resourceName);

            $this->assertFilenameExists("Blog/Presentation/Http/Resources/{$resourceName}.php");
            $this->assertFileContains([
                'namespace App\\Modules\\Blog\\Presentation\\Http\\Resources;',
                'use Illuminate\\Http\\Resources\\Json\\JsonResource;',
                "class {$resourceName} extends JsonResource",
            ], "Blog/Presentation/Http/Resources/{$resourceName}.php");
        }
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // NIVEAU 4: NAMESPACE CORRECTNESS TESTS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Test resource namespace generation is correct
     */
    #[Test]
    public function it_generates_correct_resource_namespace(): void
    {
        $this->runEasyModulesCommand('make-resource', 'Blog', 'NamespaceTest');

        // Resource should have correct namespace
        $this->assertFileContains([
            'namespace App\\Modules\\Blog\\Presentation\\Http\\Resources;',
        ], 'Blog/Presentation/Http/Resources/NamespaceTest.php');

        // Should NOT contain duplicated namespace segments
        $this->assertFileNotContains([
            'namespace App\\Modules\\Blog\\Presentation\\Http\\Resources\\Presentation\\Http\\Resources;',
            'namespace App\\Modules\\Blog\\Blog\\Presentation\\Http\\Resources;',
        ], 'Blog/Presentation/Http/Resources/NamespaceTest.php');
    }

    /**
     * Test resource structure is properly generated
     */
    #[Test]
    public function it_generates_correct_resource_structure(): void
    {
        $this->runEasyModulesCommand('make-resource', 'Blog', 'StructureTest');

        $this->assertFileContains([
            'use Illuminate\\Http\\Request;',
            'use Illuminate\\Http\\Resources\\Json\\JsonResource;',
            'class StructureTest extends JsonResource',
            'public function toArray(Request $request): array',
        ], 'Blog/Presentation/Http/Resources/StructureTest.php');
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // NIVEAU 3: DOUBLE PATH PREVENTION TESTS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Test prevention of double path and namespace duplication
     *
     * This test ensures that neither file paths nor namespaces are duplicated
     * when generating resources within the module structure.
     */
    #[Test]
    public function it_prevents_double_path_and_namespace_duplication(): void
    {
        $this->runEasyModulesCommand('make-resource', 'Blog', 'PathTest');

        // ✅ File path verification
        $this->assertFilenameExists('Blog/Presentation/Http/Resources/PathTest.php');
        $this->assertFilenameNotExists('Blog/Presentation/Http/Resources/Presentation/Http/Resources/PathTest.php');

        // ✅ Namespace duplication prevention
        $this->assertFileNotContains([
            'App\\Modules\\Blog\\Presentation\\Http\\Resources\\Presentation\\Http\\Resources',
            'App\\Modules\\Blog\\Blog\\Presentation\\Http\\Resources',
            'Presentation\\Http\\Resources\\Presentation\\Http\\Resources',
        ], 'Blog/Presentation/Http/Resources/PathTest.php');
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // NIVEAU 2: CUSTOM CONFIGURATION TESTS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Test resource generation with nested names
     */
    #[Test]
    public function it_can_generate_nested_resources(): void
    {
        $this->runEasyModulesCommand('make-resource', 'Blog', 'Api/PostResource');

        // Should create nested directory structure
        $this->assertFilenameExists('Blog/Presentation/Http/Resources/Api/PostResource.php');

        $this->assertFileContains([
            'namespace App\\Modules\\Blog\\Presentation\\Http\\Resources\\Api;',
            'class PostResource extends JsonResource',
        ], 'Blog/Presentation/Http/Resources/Api/PostResource.php');
    }

    /**
     * Test deeply nested resource generation
     */
    #[Test]
    public function it_handles_deeply_nested_resources(): void
    {
        $this->runEasyModulesCommand('make-resource', 'Blog', 'Api/V1/PostResource');

        $this->assertFilenameExists('Blog/Presentation/Http/Resources/Api/V1/PostResource.php');

        $this->assertFileContains([
            'namespace App\\Modules\\Blog\\Presentation\\Http\\Resources\\Api\\V1;',
            'class PostResource extends JsonResource',
        ], 'Blog/Presentation/Http/Resources/Api/V1/PostResource.php');

        // Should not contain path duplication in deeply nested structure
        $this->assertFileNotContains([
            'namespace App\\Modules\\Blog\\Presentation\\Http\\Resources\\Api\\V1\\Presentation\\Http\\Resources\\Api\\V1;',
        ], 'Blog/Presentation/Http/Resources/Api/V1/PostResource.php');
    }

    /**
     * Test resource generation with custom module configuration
     */
    #[Test]
    public function it_handles_custom_module_configurations(): void
    {
        // Test with custom base namespace and paths
        $this->app['config']->set('easymodules.base_namespace', 'Custom\\Modules');
        $this->app['config']->set('easymodules.paths.resource', 'Http/Resources');

        $this->runEasyModulesCommand('make-resource', 'Shop', 'ProductResource');

        // Check custom paths are used
        $this->assertFilenameExists('Shop/Http/Resources/ProductResource.php');

        // Check custom namespaces
        $this->assertFileContains([
            'namespace Custom\\Modules\\Shop\\Http\\Resources;',
            'class ProductResource extends JsonResource',
        ], 'Shop/Http/Resources/ProductResource.php');

        // Should NOT contain double namespace with custom config
        $this->assertFileNotContains([
            'namespace Custom\\Modules\\Shop\\Http\\Resources\\Http\\Resources;',
        ], 'Shop/Http/Resources/ProductResource.php');
    }

    /**
     * Test resource generation with different module structures
     */
    #[Test]
    public function it_handles_different_module_structures(): void
    {
        $testConfigs = [
            ['namespace' => 'App\\Components', 'resourcePath' => 'Resources'],
            ['namespace' => 'Modules', 'resourcePath' => 'Api/Resources'],
            ['namespace' => 'Custom\\App\\Modules', 'resourcePath' => 'Presentation/Http/Resources'],
        ];

        foreach ($testConfigs as $index => $config) {
            $this->app['config']->set('easymodules.base_namespace', $config['namespace']);
            $this->app['config']->set('easymodules.paths.resource', $config['resourcePath']);

            $resourceName = "Test{$index}Resource";

            $this->runEasyModulesCommand('make-resource', 'Test', $resourceName);

            $expectedResourcePath = "Test/{$config['resourcePath']}/{$resourceName}.php";
            $this->assertFilenameExists($expectedResourcePath);

            $expectedResourceNamespace = str_replace('/', '\\', "{$config['namespace']}\\Test\\{$config['resourcePath']}");
            $this->assertFileContains([
                "namespace {$expectedResourceNamespace};",
                "class {$resourceName}",
            ], $expectedResourcePath);

            // Should not contain duplicated path segments
            $pathSegments = explode('/', $config['resourcePath']);

            $duplicatedPatterns = [];
            foreach ($pathSegments as $segment) {
                $duplicatedPatterns[] = "{$expectedResourceNamespace}\\{$segment}\\{$segment}";
            }

            $this->assertFileNotContains($duplicatedPatterns, $expectedResourcePath);
        }
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // NIVEAU 1: EDGE CASE & INTEGRATION TESTS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Test resource generation with complex names
     */
    #[Test]
    public function it_handles_complex_resource_names(): void
    {
        $complexCases = [
            'UserProfileResource',
            'BlogPostResource',
            'OrderItemResource',
            'PaymentMethodResource',
        ];

        foreach ($complexCases as $resourceName) {
            $this->runEasyModulesCommand('make-resource', 'Test', $resourceName);

            $this->assertFilenameExists("Test/Presentation/Http/Resources/{$resourceName}.php");

            $this->assertFileContains([
                "class {$resourceName} extends JsonResource",
                'namespace App\\Modules\\Test\\Presentation\\Http\\Resources;',
            ], "Test/Presentation/Http/Resources/{$resourceName}.php");
        }
    }

    /**
     * Test resource generation edge cases
     */
    #[Test]
    public function it_handles_edge_cases_gracefully(): void
    {
        // Test with single character names
        $this->runEasyModulesCommand('make-resource', 'Blog', 'AResource');

        $this->assertFilenameExists('Blog/Presentation/Http/Resources/AResource.php');

        $this->assertFileContains([
            'class AResource extends JsonResource',
        ], 'Blog/Presentation/Http/Resources/AResource.php');

        // Test with numbers in names
        $this->runEasyModulesCommand('make-resource', 'Blog', 'User2Resource');

        $this->assertFilenameExists('Blog/Presentation/Http/Resources/User2Resource.php');

        $this->assertFileContains([
            'class User2Resource extends JsonResource',
        ], 'Blog/Presentation/Http/Resources/User2Resource.php');
    }

    /**
     * Test resource works with various module structures
     */
    #[Test]
    public function it_works_with_various_module_structures(): void
    {
        // Test multiple modules
        $modules = ['Blog', 'Shop', 'Admin'];

        foreach ($modules as $module) {
            $this->runEasyModulesCommand('make-resource', $module, 'TestResource');

            $this->assertFilenameExists("{$module}/Presentation/Http/Resources/TestResource.php");

            $this->assertFileContains([
                "namespace App\\Modules\\{$module}\\Presentation\\Http\\Resources;",
                'class TestResource extends JsonResource',
            ], "{$module}/Presentation/Http/Resources/TestResource.php");
        }
    }

    /**
     * Test multiple resources in same module
     */
    #[Test]
    public function it_handles_multiple_resources_in_same_module(): void
    {
        $resources = [
            'UserResource',
            'PostResource',
            'CommentResource',
            'Api/CategoryResource',
        ];

        foreach ($resources as $resourcePath) {
            $this->runEasyModulesCommand('make-resource', 'Blog', $resourcePath);

            $expectedFile = "Blog/Presentation/Http/Resources/{$resourcePath}.php";
            $this->assertFilenameExists($expectedFile);
        }

        // Verify all files exist and have correct content
        foreach ($resources as $resourcePath) {
            $expectedFile = "Blog/Presentation/Http/Resources/{$resourcePath}.php";
            $className    = basename($resourcePath);
            $this->assertFileContains([
                "class {$className} extends JsonResource",
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
        $this->app['config']->set('easymodules.suffixes.resource', 'Resource');

        // Test suffix addition when missing
        $this->runEasyModulesCommand('make-resource', 'Blog', 'User');

        $this->assertFilenameExists('Blog/Presentation/Http/Resources/UserResource.php');
        $this->assertFileContains([
            'class UserResource extends JsonResource',
        ], 'Blog/Presentation/Http/Resources/UserResource.php');

        // Test suffix not duplicated when already present
        $this->runEasyModulesCommand('make-resource', 'Blog', 'PostResource');

        $this->assertFilenameExists('Blog/Presentation/Http/Resources/PostResource.php');
        $this->assertFileContains([
            'class PostResource extends JsonResource',
        ], 'Blog/Presentation/Http/Resources/PostResource.php');

        // Should NOT create double suffix
        $this->assertFilenameNotExists('Blog/Presentation/Http/Resources/PostResourceResource.php');
    }

    /**
     * Test collection option behavior
     */
    #[Test]
    public function it_handles_collection_option_correctly(): void
    {
        // Regular resource should extend JsonResource
        $this->runEasyModulesCommand('make-resource', 'Blog', 'RegularResource');

        $this->assertFileContains([
            'use Illuminate\\Http\\Resources\\Json\\JsonResource;',
            'class RegularResource extends JsonResource',
        ], 'Blog/Presentation/Http/Resources/RegularResource.php');

        // Collection resource should extend ResourceCollection
        $this->runEasyModulesCommand('make-resource', 'Blog', 'CollectionResource', ['--collection' => true]);

        $this->assertFileContains([
            'use Illuminate\\Http\\Resources\\Json\\ResourceCollection;',
            'class CollectionResource extends ResourceCollection',
        ], 'Blog/Presentation/Http/Resources/CollectionResource.php');

        // Should NOT contain JsonResource for collections
        $this->assertFileNotContains([
            'extends JsonResource',
        ], 'Blog/Presentation/Http/Resources/CollectionResource.php');
    }

    /**
     * Test resource with nested structure and collection option
     */
    #[Test]
    public function it_can_generate_nested_collection_resources(): void
    {
        $this->runEasyModulesCommand('make-resource', 'Blog', 'Api/PostCollection', ['--collection' => true]);

        $this->assertFilenameExists('Blog/Presentation/Http/Resources/Api/PostCollection.php');

        $this->assertFileContains([
            'namespace App\\Modules\\Blog\\Presentation\\Http\\Resources\\Api;',
            'use Illuminate\\Http\\Resources\\Json\\ResourceCollection;',
            'class PostCollection extends ResourceCollection',
            'public function toArray(Request $request): array',
        ], 'Blog/Presentation/Http/Resources/Api/PostCollection.php');

        // Should NOT contain JsonResource for nested collections
        $this->assertFileNotContains([
            'extends JsonResource',
        ], 'Blog/Presentation/Http/Resources/Api/PostCollection.php');
    }

    /**
     * Test that generated resources have proper structure
     */
    #[Test]
    public function it_generates_proper_resource_structure(): void
    {
        $this->runEasyModulesCommand('make-resource', 'Blog', 'StructureTestResource');

        $this->assertFileContains([
            'public function toArray(Request $request): array',
            'return parent::toArray($request);',
        ], 'Blog/Presentation/Http/Resources/StructureTestResource.php');
    }

    /**
     * Test collection structure
     */
    #[Test]
    public function it_generates_proper_collection_structure(): void
    {
        $this->runEasyModulesCommand('make-resource', 'Blog', 'CollectionStructureTest', ['--collection' => true]);

        $this->assertFileContains([
            'public function toArray(Request $request): array',
            'return parent::toArray($request);',
        ], 'Blog/Presentation/Http/Resources/CollectionStructureTest.php');
    }
}
