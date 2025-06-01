<?php

declare(strict_types=1);

namespace Kadevland\EasyModules\Tests\Unit\Commands\Make\EasyModule;

use Kadevland\EasyModules\Tests\Support\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Test suite for EntityMakeCommand
 *
 * This command generates domain entities within the modular structure,
 * supporting clean architecture patterns and preventing namespace duplication.
 */
class EntityMakeCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->setComponentPath('entity', 'Domain/Entities');

        // Set app environment to testing to disable prompts
        $this->app['config']->set('app.env', 'testing');
    }

    protected function tearDown(): void
    {
        // Enhanced cleanup - remove all test files
        $testPaths = [
            'Blog/Domain/Entities',
            'Shop/Domain/Entities',
            'Test/Domain/Entities',
            'Custom/Domain/Entities',
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
    // NIVEAU 5: BASIC ENTITY GENERATION TESTS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Test basic entity file generation
     */
    #[Test]
    public function it_can_generate_basic_entity_file(): void
    {
        $this->runEasyModulesCommand('make-entity', 'Blog', 'Post');

        $this->assertModuleComponentExists('Blog', 'Domain/Entities', 'Post', [
            'class Post',
        ]);
    }

    /**
     * Test entity generation with different naming patterns
     */
    #[Test]
    public function it_can_generate_entities_with_different_names(): void
    {
        $entityNames = [
            'User',
            'BlogPost',
            'OrderItem',
            'PaymentMethod',
        ];

        foreach ($entityNames as $entityName) {
            $this->runEasyModulesCommand('make-entity', 'Blog', $entityName);

            $this->assertFilenameExists("Blog/Domain/Entities/{$entityName}.php");
            $this->assertFileContains([
                'namespace App\\Modules\\Blog\\Domain\\Entities;',
                "class {$entityName}",
            ], "Blog/Domain/Entities/{$entityName}.php");
        }
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // NIVEAU 4: NAMESPACE CORRECTNESS TESTS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Test entity namespace generation is correct
     */
    #[Test]
    public function it_generates_correct_entity_namespace(): void
    {
        $this->runEasyModulesCommand('make-entity', 'Blog', 'NamespaceTest');

        // Entity should have correct namespace
        $this->assertFileContains([
            'namespace App\\Modules\\Blog\\Domain\\Entities;',
        ], 'Blog/Domain/Entities/NamespaceTest.php');

        // Should NOT contain duplicated namespace segments
        $this->assertFileNotContains([
            'namespace App\\Modules\\Blog\\Domain\\Entities\\Domain\\Entities;',
            'namespace App\\Modules\\Blog\\Blog\\Domain\\Entities;',
        ], 'Blog/Domain/Entities/NamespaceTest.php');
    }

    /**
     * Test entity structure is properly generated
     */
    #[Test]
    public function it_generates_correct_entity_structure(): void
    {
        $this->runEasyModulesCommand('make-entity', 'Blog', 'StructureTest');

        $this->assertFileContains([
            'class StructureTest',
        ], 'Blog/Domain/Entities/StructureTest.php');
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // NIVEAU 3: DOUBLE PATH PREVENTION TESTS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Test prevention of double path and namespace duplication
     *
     * This test ensures that neither file paths nor namespaces are duplicated
     * when generating entities within the module structure.
     */
    #[Test]
    public function it_prevents_double_path_and_namespace_duplication(): void
    {
        $this->runEasyModulesCommand('make-entity', 'Blog', 'PathTest');

        // ✅ File path verification
        $this->assertFilenameExists('Blog/Domain/Entities/PathTest.php');
        $this->assertFilenameNotExists('Blog/Domain/Entities/Domain/Entities/PathTest.php');

        // ✅ Namespace duplication prevention
        $this->assertFileNotContains([
            'App\\Modules\\Blog\\Domain\\Entities\\Domain\\Entities',
            'App\\Modules\\Blog\\Blog\\Domain\\Entities',
            'Domain\\Entities\\Domain\\Entities',
        ], 'Blog/Domain/Entities/PathTest.php');
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // NIVEAU 2: CUSTOM CONFIGURATION TESTS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Test entity generation with nested names
     */
    #[Test]
    public function it_can_generate_nested_entities(): void
    {
        $this->runEasyModulesCommand('make-entity', 'Blog', 'User/Profile');

        // Should create nested directory structure
        $this->assertFilenameExists('Blog/Domain/Entities/User/Profile.php');

        $this->assertFileContains([
            'namespace App\\Modules\\Blog\\Domain\\Entities\\User;',
            'class Profile',
        ], 'Blog/Domain/Entities/User/Profile.php');
    }

    /**
     * Test deeply nested entity generation
     */
    #[Test]
    public function it_handles_deeply_nested_entities(): void
    {
        $this->runEasyModulesCommand('make-entity', 'Blog', 'Product/Category/Subcategory');

        $this->assertFilenameExists('Blog/Domain/Entities/Product/Category/Subcategory.php');

        $this->assertFileContains([
            'namespace App\\Modules\\Blog\\Domain\\Entities\\Product\\Category;',
            'class Subcategory',
        ], 'Blog/Domain/Entities/Product/Category/Subcategory.php');

        // Should not contain path duplication in deeply nested structure
        $this->assertFileNotContains([
            'namespace App\\Modules\\Blog\\Domain\\Entities\\Product\\Category\\Domain\\Entities\\Product\\Category;',
        ], 'Blog/Domain/Entities/Product/Category/Subcategory.php');
    }

    /**
     * Test entity generation with custom module configuration
     */
    #[Test]
    public function it_handles_custom_module_configurations(): void
    {
        // Test with custom base namespace and paths
        $this->app['config']->set('easymodules.base_namespace', 'Custom\\Modules');
        $this->app['config']->set('easymodules.paths.entity', 'Core/Entities');

        $this->runEasyModulesCommand('make-entity', 'Shop', 'Product');

        // Check custom paths are used
        $this->assertFilenameExists('Shop/Core/Entities/Product.php');

        // Check custom namespaces
        $this->assertFileContains([
            'namespace Custom\\Modules\\Shop\\Core\\Entities;',
            'class Product',
        ], 'Shop/Core/Entities/Product.php');

        // Should NOT contain double namespace with custom config
        $this->assertFileNotContains([
            'namespace Custom\\Modules\\Shop\\Core\\Entities\\Core\\Entities;',
        ], 'Shop/Core/Entities/Product.php');
    }

    /**
     * Test entity generation with different module structures
     */
    #[Test]
    public function it_handles_different_module_structures(): void
    {
        $testConfigs = [
            ['namespace' => 'App\\Components', 'entityPath' => 'Entities'],
            ['namespace' => 'Modules', 'entityPath' => 'Domain/Models'],
            ['namespace' => 'Custom\\App\\Modules', 'entityPath' => 'Domain/Entities'],
        ];

        foreach ($testConfigs as $index => $config) {
            $this->app['config']->set('easymodules.base_namespace', $config['namespace']);
            $this->app['config']->set('easymodules.paths.entity', $config['entityPath']);

            $entityName = "Test{$index}Entity";

            $this->runEasyModulesCommand('make-entity', 'Test', $entityName);

            $expectedEntityPath = "Test/{$config['entityPath']}/{$entityName}.php";
            $this->assertFilenameExists($expectedEntityPath);

            $expectedEntityNamespace = str_replace('/', '\\', "{$config['namespace']}\\Test\\{$config['entityPath']}");
            $this->assertFileContains([
                "namespace {$expectedEntityNamespace};",
                "class {$entityName}",
            ], $expectedEntityPath);

            // Should not contain duplicated path segments
            $pathSegments = explode('/', $config['entityPath']);

            $duplicatedPatterns = [];
            foreach ($pathSegments as $segment) {
                $duplicatedPatterns[] = "{$expectedEntityNamespace}\\{$segment}\\{$segment}";
            }

            $this->assertFileNotContains($duplicatedPatterns, $expectedEntityPath);
        }
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // NIVEAU 1: EDGE CASE & INTEGRATION TESTS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Test entity generation with complex names
     */
    #[Test]
    public function it_handles_complex_entity_names(): void
    {
        $complexCases = [
            'UserProfile',
            'BlogPostMetadata',
            'PaymentTransaction',
            'ShippingAddress',
        ];

        foreach ($complexCases as $entityName) {
            $this->runEasyModulesCommand('make-entity', 'Test', $entityName);

            $this->assertFilenameExists("Test/Domain/Entities/{$entityName}.php");

            $this->assertFileContains([
                "class {$entityName}",
                'namespace App\\Modules\\Test\\Domain\\Entities;',
            ], "Test/Domain/Entities/{$entityName}.php");
        }
    }

    /**
     * Test entity generation edge cases
     */
    #[Test]
    public function it_handles_edge_cases_gracefully(): void
    {
        // Test with single character names
        $this->runEasyModulesCommand('make-entity', 'Blog', 'A');

        $this->assertFilenameExists('Blog/Domain/Entities/A.php');
        $this->assertFileContains([
            'class A',
        ], 'Blog/Domain/Entities/A.php');

        // Test with numbers in names
        $this->runEasyModulesCommand('make-entity', 'Blog', 'User2Factor');

        $this->assertFilenameExists('Blog/Domain/Entities/User2Factor.php');
        $this->assertFileContains([
            'class User2Factor',
        ], 'Blog/Domain/Entities/User2Factor.php');
    }

    /**
     * Test entity works with various module structures
     */
    #[Test]
    public function it_works_with_various_module_structures(): void
    {
        // Test multiple modules
        $modules = ['Blog', 'Shop', 'Admin'];

        foreach ($modules as $module) {
            $this->runEasyModulesCommand('make-entity', $module, 'TestEntity');

            $this->assertFilenameExists("{$module}/Domain/Entities/TestEntity.php");

            $this->assertFileContains([
                "namespace App\\Modules\\{$module}\\Domain\\Entities;",
                'class TestEntity',
            ], "{$module}/Domain/Entities/TestEntity.php");
        }
    }

    /**
     * Test multiple entities in same module
     */
    #[Test]
    public function it_handles_multiple_entities_in_same_module(): void
    {
        $entities = [
            'User',
            'Post',
            'Comment',
            'Category/Subcategory',
        ];

        foreach ($entities as $entityPath) {
            $this->runEasyModulesCommand('make-entity', 'Blog', $entityPath);

            $expectedFile = "Blog/Domain/Entities/{$entityPath}.php";
            $this->assertFilenameExists($expectedFile);
        }

        // Verify all files exist and have correct content
        foreach ($entities as $entityPath) {
            $expectedFile = "Blog/Domain/Entities/{$entityPath}.php";
            $className = basename($entityPath);
            $this->assertFileContains([
                "class {$className}",
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
        $this->app['config']->set('easymodules.suffixes.entity', 'Entity');

        // Test suffix addition when missing
        $this->runEasyModulesCommand('make-entity', 'Blog', 'User');

        $this->assertFilenameExists('Blog/Domain/Entities/UserEntity.php');
        $this->assertFileContains([
            'class UserEntity',
        ], 'Blog/Domain/Entities/UserEntity.php');

        // Test suffix not duplicated when already present
        $this->runEasyModulesCommand('make-entity', 'Blog', 'PostEntity');

        $this->assertFilenameExists('Blog/Domain/Entities/PostEntity.php');
        $this->assertFileContains([
            'class PostEntity',
        ], 'Blog/Domain/Entities/PostEntity.php');

        // Should NOT create double suffix
        $this->assertFilenameNotExists('Blog/Domain/Entities/PostEntityEntity.php');
    }

    /**
     * Test entity generation follows domain-driven design patterns
     */
    #[Test]
    public function it_follows_domain_driven_design_patterns(): void
    {
        $domainEntities = [
            'User',
            'Order',
            'Product',
            'Customer',
            'Invoice',
        ];

        foreach ($domainEntities as $entityName) {
            $this->runEasyModulesCommand('make-entity', 'Blog', $entityName);

            $this->assertFilenameExists("Blog/Domain/Entities/{$entityName}.php");

            $this->assertFileContains([
                "class {$entityName}",
                'namespace App\\Modules\\Blog\\Domain\\Entities;',
            ], "Blog/Domain/Entities/{$entityName}.php");
        }
    }

    /**
     * Test entity with clean architecture compliance
     */
    #[Test]
    public function it_maintains_clean_architecture_compliance(): void
    {
        $this->runEasyModulesCommand('make-entity', 'Blog', 'CleanEntity');

        // Should be in Domain layer (correct architectural layer)
        $this->assertFilenameExists('Blog/Domain/Entities/CleanEntity.php');

        // Should have clean namespace structure
        $this->assertFileContains([
            'namespace App\\Modules\\Blog\\Domain\\Entities;',
            'class CleanEntity',
        ], 'Blog/Domain/Entities/CleanEntity.php');

        // Should NOT leak into other architectural layers
        $this->assertFilenameNotExists('Blog/Infrastructure/Entities/CleanEntity.php');
        $this->assertFilenameNotExists('Blog/Application/Entities/CleanEntity.php');
        $this->assertFilenameNotExists('Blog/Presentation/Entities/CleanEntity.php');
    }
}
