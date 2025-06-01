<?php

declare(strict_types=1);

namespace Kadevland\EasyModules\Tests\Unit\Commands\Make\Laravel;

use Kadevland\EasyModules\Tests\Support\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Test suite for SeederMakeCommand
 *
 * This command extends Laravel's base SeederMakeCommand to generate
 * database seeder classes within the modular structure.
 */
class SeederMakeCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->setComponentPath('seeder', 'Database/Seeders');

        // Set app environment to testing to disable prompts
        $this->app['config']->set('app.env', 'testing');
    }

    protected function tearDown(): void
    {
        // Enhanced cleanup - remove all test files
        $testPaths = [
            'Blog/Database/Seeders',
            'Shop/Database/Seeders',
            'Test/Database/Seeders',
            'Custom/Data/Seeders',
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
    // NIVEAU 5: BASIC SEEDER GENERATION TESTS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Test basic seeder file generation
     */
    #[Test]
    public function it_can_generate_basic_seeder_file(): void
    {
        $this->runEasyModulesCommand('make-seeder', 'Blog', 'PostSeeder');

        $this->assertModuleComponentExists('Blog', 'Database/Seeders', 'PostSeeder', [
            'use Illuminate\Database\Seeder;',
            'class PostSeeder extends Seeder',
            'public function run(): void',
        ]);
    }

    /**
     * Test seeder generation with different naming patterns
     */
    #[Test]
    public function it_can_generate_seeders_with_different_names(): void
    {
        $seederNames = [
            'UserSeeder',
            'PostSeeder',
            'CategorySeeder',
            'TagSeeder',
        ];

        foreach ($seederNames as $seederName) {
            $this->runEasyModulesCommand('make-seeder', 'Blog', $seederName);

            $this->assertFilenameExists("Blog/Database/Seeders/{$seederName}.php");
            $this->assertFileContains([
                'namespace App\\Modules\\Blog\\Database\\Seeders;',
                'use Illuminate\\Database\\Seeder;',
                "class {$seederName} extends Seeder",
            ], "Blog/Database/Seeders/{$seederName}.php");
        }
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // NIVEAU 4: NAMESPACE CORRECTNESS TESTS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Test seeder namespace generation is correct
     */
    #[Test]
    public function it_generates_correct_seeder_namespace(): void
    {
        $this->runEasyModulesCommand('make-seeder', 'Blog', 'NamespaceTest');

        // Seeder should have correct namespace
        $this->assertFileContains([
            'namespace App\\Modules\\Blog\\Database\\Seeders;',
        ], 'Blog/Database/Seeders/NamespaceTest.php');

        // Should NOT contain duplicated namespace segments
        $this->assertFileNotContains([
            'namespace App\\Modules\\Blog\\Database\\Seeders\\Database\\Seeders;',
            'namespace App\\Modules\\Blog\\Blog\\Database\\Seeders;',
        ], 'Blog/Database/Seeders/NamespaceTest.php');
    }

    /**
     * Test seeder structure is properly generated
     */
    #[Test]
    public function it_generates_correct_seeder_structure(): void
    {
        $this->runEasyModulesCommand('make-seeder', 'Blog', 'StructureTest');

        $this->assertFileContains([
            'use Illuminate\\Database\\Seeder;',
            'class StructureTest extends Seeder',
            'public function run(): void',
        ], 'Blog/Database/Seeders/StructureTest.php');
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // NIVEAU 3: DOUBLE PATH PREVENTION TESTS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Test prevention of double path and namespace duplication
     *
     * This test ensures that neither file paths nor namespaces are duplicated
     * when generating seeders within the module structure.
     */
    #[Test]
    public function it_prevents_double_path_and_namespace_duplication(): void
    {
        $this->runEasyModulesCommand('make-seeder', 'Blog', 'PathTest');

        // ✅ File path verification
        $this->assertFilenameExists('Blog/Database/Seeders/PathTest.php');
        $this->assertFilenameNotExists('Blog/Database/Seeders/Database/Seeders/PathTest.php');

        // ✅ Namespace duplication prevention
        $this->assertFileNotContains([
            'App\\Modules\\Blog\\Database\\Seeders\\Database\\Seeders',
            'App\\Modules\\Blog\\Blog\\Database\\Seeders',
            'Database\\Seeders\\Database\\Seeders',
        ], 'Blog/Database/Seeders/PathTest.php');
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // NIVEAU 2: CUSTOM CONFIGURATION TESTS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Test seeder generation with nested names
     */
    #[Test]
    public function it_can_generate_nested_seeders(): void
    {
        $this->runEasyModulesCommand('make-seeder', 'Blog', 'Content/PostSeeder');

        // Should create nested directory structure
        $this->assertFilenameExists('Blog/Database/Seeders/Content/PostSeeder.php');

        $this->assertFileContains([
            'namespace App\\Modules\\Blog\\Database\\Seeders\\Content;',
            'class PostSeeder extends Seeder',
        ], 'Blog/Database/Seeders/Content/PostSeeder.php');
    }

    /**
     * Test deeply nested seeder generation
     */
    #[Test]
    public function it_handles_deeply_nested_seeders(): void
    {
        $this->runEasyModulesCommand('make-seeder', 'Blog', 'Test/Data/SampleSeeder');

        $this->assertFilenameExists('Blog/Database/Seeders/Test/Data/SampleSeeder.php');

        $this->assertFileContains([
            'namespace App\\Modules\\Blog\\Database\\Seeders\\Test\\Data;',
            'class SampleSeeder extends Seeder',
        ], 'Blog/Database/Seeders/Test/Data/SampleSeeder.php');

        // Should not contain path duplication in deeply nested structure
        $this->assertFileNotContains([
            'namespace App\\Modules\\Blog\\Database\\Seeders\\Test\\Data\\Database\\Seeders\\Test\\Data;',
        ], 'Blog/Database/Seeders/Test/Data/SampleSeeder.php');
    }

    /**
     * Test seeder generation with custom module configuration
     */
    #[Test]
    public function it_handles_custom_module_configurations(): void
    {
        // Test with custom base namespace and paths
        $this->app['config']->set('easymodules.base_namespace', 'Custom\\Modules');
        $this->app['config']->set('easymodules.paths.seeder', 'Data/Seeders');

        $this->runEasyModulesCommand('make-seeder', 'Shop', 'ProductSeeder');

        // Check custom paths are used
        $this->assertFilenameExists('Shop/Data/Seeders/ProductSeeder.php');

        // Check custom namespaces
        $this->assertFileContains([
            'namespace Custom\\Modules\\Shop\\Data\\Seeders;',
            'class ProductSeeder extends Seeder',
        ], 'Shop/Data/Seeders/ProductSeeder.php');

        // Should NOT contain double namespace with custom config
        $this->assertFileNotContains([
            'namespace Custom\\Modules\\Shop\\Data\\Seeders\\Data\\Seeders;',
        ], 'Shop/Data/Seeders/ProductSeeder.php');
    }

    /**
     * Test seeder generation with different module structures
     */
    #[Test]
    public function it_handles_different_module_structures(): void
    {
        $testConfigs = [
            ['namespace' => 'App\\Components', 'seederPath' => 'Seeders'],
            ['namespace' => 'Modules', 'seederPath' => 'Database/Seeders'],
            ['namespace' => 'Custom\\App\\Modules', 'seederPath' => 'Infrastructure/Seeders'],
        ];

        foreach ($testConfigs as $index => $config) {
            $this->app['config']->set('easymodules.base_namespace', $config['namespace']);
            $this->app['config']->set('easymodules.paths.seeder', $config['seederPath']);

            $seederName = "Test{$index}Seeder";

            $this->runEasyModulesCommand('make-seeder', 'Test', $seederName);

            $expectedSeederPath = "Test/{$config['seederPath']}/{$seederName}.php";
            $this->assertFilenameExists($expectedSeederPath);

            $expectedSeederNamespace = str_replace('/', '\\', "{$config['namespace']}\\Test\\{$config['seederPath']}");
            $this->assertFileContains([
                "namespace {$expectedSeederNamespace};",
                "class {$seederName}",
            ], $expectedSeederPath);

            // Should not contain duplicated path segments
            $pathSegments = explode('/', $config['seederPath']);

            $duplicatedPatterns = [];
            foreach ($pathSegments as $segment) {
                $duplicatedPatterns[] = "{$expectedSeederNamespace}\\{$segment}\\{$segment}";
            }

            $this->assertFileNotContains($duplicatedPatterns, $expectedSeederPath);
        }
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // NIVEAU 1: EDGE CASE & INTEGRATION TESTS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Test seeder generation with complex names
     */
    #[Test]
    public function it_handles_complex_seeder_names(): void
    {
        $complexCases = [
            'UserProfileSeeder',
            'BlogPostCategorySeeder',
            'AdminUserSeeder',
            'ProductCatalogSeeder',
        ];

        foreach ($complexCases as $seederName) {
            $this->runEasyModulesCommand('make-seeder', 'Test', $seederName);

            $this->assertFilenameExists("Test/Database/Seeders/{$seederName}.php");

            $this->assertFileContains([
                "class {$seederName} extends Seeder",
                'namespace App\\Modules\\Test\\Database\\Seeders;',
            ], "Test/Database/Seeders/{$seederName}.php");
        }
    }

    /**
     * Test seeder generation edge cases
     */
    #[Test]
    public function it_handles_edge_cases_gracefully(): void
    {
        // Test with single character names
        $this->runEasyModulesCommand('make-seeder', 'Blog', 'ASeeder');

        $this->assertFilenameExists('Blog/Database/Seeders/ASeeder.php');

        $this->assertFileContains([
            'class ASeeder extends Seeder',
        ], 'Blog/Database/Seeders/ASeeder.php');

        // Test with numbers in names
        $this->runEasyModulesCommand('make-seeder', 'Blog', 'User2Seeder');

        $this->assertFilenameExists('Blog/Database/Seeders/User2Seeder.php');

        $this->assertFileContains([
            'class User2Seeder extends Seeder',
        ], 'Blog/Database/Seeders/User2Seeder.php');
    }

    /**
     * Test seeder works with various module structures
     */
    #[Test]
    public function it_works_with_various_module_structures(): void
    {
        // Test multiple modules
        $modules = ['Blog', 'Shop', 'Admin'];

        foreach ($modules as $module) {
            $this->runEasyModulesCommand('make-seeder', $module, 'TestSeeder');

            $this->assertFilenameExists("{$module}/Database/Seeders/TestSeeder.php");

            $this->assertFileContains([
                "namespace App\\Modules\\{$module}\\Database\\Seeders;",
                'class TestSeeder extends Seeder',
            ], "{$module}/Database/Seeders/TestSeeder.php");
        }
    }

    /**
     * Test multiple seeders in same module
     */
    #[Test]
    public function it_handles_multiple_seeders_in_same_module(): void
    {
        $seeders = [
            'UserSeeder',
            'PostSeeder',
            'CategorySeeder',
            'Content/TagSeeder',
        ];

        foreach ($seeders as $seederPath) {
            $this->runEasyModulesCommand('make-seeder', 'Blog', $seederPath);

            $expectedFile = "Blog/Database/Seeders/{$seederPath}.php";
            $this->assertFilenameExists($expectedFile);
        }

        // Verify all files exist and have correct content
        foreach ($seeders as $seederPath) {
            $expectedFile = "Blog/Database/Seeders/{$seederPath}.php";
            $className    = basename($seederPath);
            $this->assertFileContains([
                "class {$className} extends Seeder",
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
        $this->app['config']->set('easymodules.suffixes.seeder', 'Seeder');

        // Test suffix addition when missing
        $this->runEasyModulesCommand('make-seeder', 'Blog', 'User');

        $this->assertFilenameExists('Blog/Database/Seeders/UserSeeder.php');
        $this->assertFileContains([
            'class UserSeeder extends Seeder',
        ], 'Blog/Database/Seeders/UserSeeder.php');

        // Test suffix not duplicated when already present
        $this->runEasyModulesCommand('make-seeder', 'Blog', 'PostSeeder');

        $this->assertFilenameExists('Blog/Database/Seeders/PostSeeder.php');
        $this->assertFileContains([
            'class PostSeeder extends Seeder',
        ], 'Blog/Database/Seeders/PostSeeder.php');

        // Should NOT create double suffix
        $this->assertFilenameNotExists('Blog/Database/Seeders/PostSeederSeeder.php');
    }

    /**
     * Test that generated seeders have proper structure
     */
    #[Test]
    public function it_generates_proper_seeder_structure(): void
    {
        $this->runEasyModulesCommand('make-seeder', 'Blog', 'StructureTestSeeder');

        $this->assertFileContains([
            'public function run(): void',
            '//',
        ], 'Blog/Database/Seeders/StructureTestSeeder.php');
    }

    /**
     * Test seeders with database table pattern
     */
    #[Test]
    public function it_handles_database_table_patterns(): void
    {
        $tablePatterns = [
            'UsersTableSeeder',
            'PostsTableSeeder',
            'CategoriesTableSeeder',
        ];

        foreach ($tablePatterns as $seederName) {
            $this->runEasyModulesCommand('make-seeder', 'Blog', $seederName);

            $this->assertFilenameExists("Blog/Database/Seeders/{$seederName}.php");
            $this->assertFileContains([
                "class {$seederName} extends Seeder",
                'public function run(): void',
            ], "Blog/Database/Seeders/{$seederName}.php");
        }
    }
}
