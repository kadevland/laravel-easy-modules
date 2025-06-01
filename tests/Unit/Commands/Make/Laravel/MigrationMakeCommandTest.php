<?php

declare(strict_types=1);

namespace Kadevland\EasyModules\Tests\Unit\Commands\Make\Laravel;

use Kadevland\EasyModules\Tests\Support\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Test suite for MigrationMakeCommand
 *
 * This command extends Laravel's base MigrationMakeCommand to generate
 * migration files within the modular structure, supporting all Laravel
 * options like --create, --table, and --update.
 */
class MigrationMakeCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->setComponentPath('migration', 'Database/Migrations');

        // Set app environment to testing to disable prompts
        $this->app['config']->set('app.env', 'testing');
    }

    protected function tearDown(): void
    {
        // Enhanced cleanup - remove all test files
        $testPaths = [
            'Blog/Database/Migrations',
            'Shop/Database/Migrations',
            'Test/Database/Migrations',
            'Custom/Data/Migrations',
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
    // NIVEAU 5: BASIC MIGRATION GENERATION TESTS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Test basic migration file generation
     */
    #[Test]
    public function it_can_generate_basic_migration_file(): void
    {
        $this->runEasyModulesCommand('make-migration', 'Blog', 'CreatePostsTable');

        // Migration files have timestamp prefixes, so we need to check differently
        $migrationFiles = glob($this->testBasePath('Blog/Database/Migrations/*_create_posts_table.php'));
        $this->assertNotEmpty($migrationFiles, 'Migration file should be created');

        $migrationContent = file_get_contents($migrationFiles[0]);
        $this->assertStringContainsString('use Illuminate\Database\Migrations\Migration;', $migrationContent);
        $this->assertStringContainsString('return new class extends Migration', $migrationContent);
    }

    /**
     * Test migration generation with create option
     */
    #[Test]
    public function it_can_generate_migration_with_create_option(): void
    {
        $this->runEasyModulesCommand('make-migration', 'Blog', 'CreateUsersTable', ['--create' => 'users']);

        $migrationFiles = glob($this->testBasePath('Blog/Database/Migrations/*_create_users_table.php'));
        $this->assertNotEmpty($migrationFiles, 'Migration file should be created');

        $migrationContent = file_get_contents($migrationFiles[0]);
        $this->assertStringContainsString("Schema::create('users'", $migrationContent);
        $this->assertStringContainsString("Schema::dropIfExists('users')", $migrationContent);
    }

    /**
     * Test migration generation with table option
     */
    #[Test]
    public function it_can_generate_migration_with_table_option(): void
    {
        $this->runEasyModulesCommand('make-migration', 'Blog', 'AddStatusToPostsTable', ['--table' => 'posts']);

        $migrationFiles = glob($this->testBasePath('Blog/Database/Migrations/*_add_status_to_posts_table.php'));
        $this->assertNotEmpty($migrationFiles, 'Migration file should be created');

        $migrationContent = file_get_contents($migrationFiles[0]);
        $this->assertStringContainsString("Schema::table('posts'", $migrationContent);
    }

    /**
     * Test migration generation with different naming patterns
     */
    #[Test]
    public function it_can_generate_migrations_with_different_names(): void
    {
        $migrationNames = [
            'CreateCategoriesTable',
            'AddIndexToUsersTable',
            'UpdatePostsTableStructure',
            'CreateTagsTable',
        ];

        foreach ($migrationNames as $migrationName) {
            $this->runEasyModulesCommand('make-migration', 'Blog', $migrationName);

            // Convert to snake_case for file matching
            $snakeName      = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $migrationName));
            $migrationFiles = glob($this->testBasePath("Blog/Database/Migrations/*_{$snakeName}.php"));
            $this->assertNotEmpty($migrationFiles, "Migration file should be created for {$migrationName}");
        }
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // NIVEAU 4: NAMESPACE CORRECTNESS TESTS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Test migration structure is properly generated
     */
    #[Test]
    public function it_generates_correct_migration_structure(): void
    {
        $this->runEasyModulesCommand('make-migration', 'Blog', 'CreateTestTable');

        $migrationFiles = glob($this->testBasePath('Blog/Database/Migrations/*_create_test_table.php'));
        $this->assertNotEmpty($migrationFiles, 'Migration file should be created');

        $migrationContent = file_get_contents($migrationFiles[0]);

        // Laravel 12 uses anonymous classes for migrations
        $this->assertStringContainsString('use Illuminate\Database\Migrations\Migration;', $migrationContent);
        $this->assertStringContainsString('use Illuminate\Database\Schema\Blueprint;', $migrationContent);
        $this->assertStringContainsString('use Illuminate\Support\Facades\Schema;', $migrationContent);
        $this->assertStringContainsString('return new class extends Migration', $migrationContent);
        $this->assertStringContainsString('public function up(): void', $migrationContent);
        $this->assertStringContainsString('public function down(): void', $migrationContent);
    }

    /**
     * Test migration directory structure
     */
    #[Test]
    public function it_creates_migrations_in_correct_directory(): void
    {
        $this->runEasyModulesCommand('make-migration', 'Blog', 'CreateExampleTable');

        // Migration should be in the module's Database/Migrations directory
        $migrationFiles = glob($this->testBasePath('Blog/Database/Migrations/*_create_example_table.php'));
        $this->assertNotEmpty($migrationFiles, 'Migration should be in module directory');

        // Should NOT be in global migrations directory
        $globalMigrationFiles = glob(database_path('migrations/*_create_example_table.php'));
        $this->assertEmpty($globalMigrationFiles, 'Migration should not be in global migrations');
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // NIVEAU 3: DOUBLE PATH PREVENTION TESTS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Test prevention of double path and namespace duplication
     *
     * This test ensures that migration files are placed in correct locations
     * without path duplication within the module structure.
     */
    #[Test]
    public function it_prevents_double_path_and_namespace_duplication(): void
    {
        $this->runEasyModulesCommand('make-migration', 'Blog', 'CreatePathTestTable');

        // ✅ File path verification
        $migrationFiles = glob($this->testBasePath('Blog/Database/Migrations/*_create_path_test_table.php'));
        $this->assertNotEmpty($migrationFiles, 'Migration should exist in correct path');

        // ✅ Should NOT be in duplicated path
        $duplicatedPathFiles = glob($this->testBasePath('Blog/Database/Migrations/Database/Migrations/*_create_path_test_table.php'));
        $this->assertEmpty($duplicatedPathFiles, 'Migration should not be in duplicated path');

        // Migrations use anonymous classes, so no namespace duplication issues
        $migrationContent = file_get_contents($migrationFiles[0]);
        $this->assertStringContainsString('return new class extends Migration', $migrationContent);
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // NIVEAU 2: CUSTOM CONFIGURATION TESTS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Test migration generation with custom module configuration
     */
    #[Test]
    public function it_handles_custom_module_configurations(): void
    {
        // Test with custom base namespace and paths
        $this->app['config']->set('easymodules.base_namespace', 'Custom\\Modules');
        $this->app['config']->set('easymodules.paths.migration', 'Data/Migrations');

        $this->runEasyModulesCommand('make-migration', 'Shop', 'CreateProductsTable');

        // Check custom paths are used
        $migrationFiles = glob($this->testBasePath('Shop/Data/Migrations/*_create_products_table.php'));
        $this->assertNotEmpty($migrationFiles, 'Migration should be created in custom path');

        // Migration content should be correct regardless of custom paths
        $migrationContent = file_get_contents($migrationFiles[0]);
        $this->assertStringContainsString('return new class extends Migration', $migrationContent);
    }

    /**
     * Test migration generation with different module structures
     */
    #[Test]
    public function it_handles_different_module_structures(): void
    {
        $testConfigs = [
            ['namespace' => 'App\\Components', 'migrationPath' => 'Migrations'],
            ['namespace' => 'Modules', 'migrationPath' => 'Database/Migrations'],
            ['namespace' => 'Custom\\App\\Modules', 'migrationPath' => 'Infrastructure/Migrations'],
        ];

        foreach ($testConfigs as $index => $config) {
            $this->app['config']->set('easymodules.base_namespace', $config['namespace']);
            $this->app['config']->set('easymodules.paths.migration', $config['migrationPath']);

            $migrationName = "CreateTest{$index}Table";

            $this->runEasyModulesCommand('make-migration', 'Test', $migrationName);

            $snakeName             = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $migrationName));
            $expectedMigrationPath = "Test/{$config['migrationPath']}/*_{$snakeName}.php";
            $migrationFiles        = glob($this->testBasePath($expectedMigrationPath));
            $this->assertNotEmpty($migrationFiles, "Migration should be created in {$config['migrationPath']}");

            // Should not contain duplicated path segments
            $duplicatedPath  = "Test/{$config['migrationPath']}/{$config['migrationPath']}/*_{$snakeName}.php";
            $duplicatedFiles = glob($this->testBasePath($duplicatedPath));
            $this->assertEmpty($duplicatedFiles, 'Migration should not be in duplicated path');
        }
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // NIVEAU 1: EDGE CASE & INTEGRATION TESTS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Test migration generation with complex names
     */
    #[Test]
    public function it_handles_complex_migration_names(): void
    {
        $complexCases = [
            'CreateUserProfilesTable',
            'AddFullTextIndexToPostsTable',
            'CreateBlogPostCategoriesTable',
            'UpdateUsersTableAddEmailVerifiedAt',
        ];

        foreach ($complexCases as $migrationName) {
            $this->runEasyModulesCommand('make-migration', 'Test', $migrationName);

            $snakeName      = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $migrationName));
            $migrationFiles = glob($this->testBasePath("Test/Database/Migrations/*_{$snakeName}.php"));
            $this->assertNotEmpty($migrationFiles, "Complex migration {$migrationName} should be created");

            $migrationContent = file_get_contents($migrationFiles[0]);
            $this->assertStringContainsString('return new class extends Migration', $migrationContent);
        }
    }

    /**
     * Test migration generation edge cases
     */
    #[Test]
    public function it_handles_edge_cases_gracefully(): void
    {
        // Test with simple table name
        $this->runEasyModulesCommand('make-migration', 'Blog', 'CreateATable');

        $migrationFiles = glob($this->testBasePath('Blog/Database/Migrations/*_create_a_table.php'));
        $this->assertNotEmpty($migrationFiles, 'Simple migration should be created');

        // Test with numbers in names
        $this->runEasyModulesCommand('make-migration', 'Blog', 'CreateUser2Table');

        $migrationFiles = glob($this->testBasePath('Blog/Database/Migrations/*_create_user2_table.php'));
        $this->assertNotEmpty($migrationFiles, 'Migration with numbers should be created');
    }

    /**
     * Test migration works with various module structures
     */
    #[Test]
    public function it_works_with_various_module_structures(): void
    {
        // Test multiple modules
        $modules = ['Blog', 'Shop', 'Admin'];

        foreach ($modules as $module) {
            $this->runEasyModulesCommand('make-migration', $module, 'CreateTestTable');

            $migrationFiles = glob($this->testBasePath("{$module}/Database/Migrations/*_create_test_table.php"));
            $this->assertNotEmpty($migrationFiles, "Migration should be created in {$module} module");
        }
    }

    /**
     * Test multiple migrations in same module
     */
    #[Test]
    public function it_handles_multiple_migrations_in_same_module(): void
    {
        $migrations = [
            'CreateUsersTable',
            'CreatePostsTable',
            'AddIndexToUsersTable',
            'CreateCategoriesTable',
        ];

        foreach ($migrations as $migrationName) {
            $this->runEasyModulesCommand('make-migration', 'Blog', $migrationName);

            $snakeName      = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $migrationName));
            $migrationFiles = glob($this->testBasePath("Blog/Database/Migrations/*_{$snakeName}.php"));
            $this->assertNotEmpty($migrationFiles, "Migration {$migrationName} should be created");
        }

        // Verify all migrations exist and have correct content
        foreach ($migrations as $migrationName) {
            $snakeName      = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $migrationName));
            $migrationFiles = glob($this->testBasePath("Blog/Database/Migrations/*_{$snakeName}.php"));

            $migrationContent = file_get_contents($migrationFiles[0]);
            $this->assertStringContainsString('return new class extends Migration', $migrationContent);
        }
    }

    /**
     * Test migration filename format
     */
    #[Test]
    public function it_generates_correct_migration_filename_format(): void
    {
        $this->runEasyModulesCommand('make-migration', 'Blog', 'CreateExampleTable');

        $migrationFiles = glob($this->testBasePath('Blog/Database/Migrations/*_create_example_table.php'));
        $this->assertNotEmpty($migrationFiles, 'Migration file should be created');

        $filename = basename($migrationFiles[0]);

        // Should have timestamp prefix format: YYYY_MM_DD_HHMMSS_create_example_table.php
        $this->assertMatchesRegularExpression(
            '/^\d{4}_\d{2}_\d{2}_\d{6}_create_example_table\.php$/',
            $filename,
            'Migration filename should have correct timestamp format'
        );
    }

    /**
     * Test migration with create table structure
     */
    #[Test]
    public function it_generates_create_table_migration_structure(): void
    {
        $this->runEasyModulesCommand('make-migration', 'Blog', 'CreateProductsTable', ['--create' => 'products']);

        $migrationFiles   = glob($this->testBasePath('Blog/Database/Migrations/*_create_products_table.php'));
        $migrationContent = file_get_contents($migrationFiles[0]);

        // Should contain create table structure
        $this->assertStringContainsString("Schema::create('products', function (Blueprint \$table) {", $migrationContent);
        $this->assertStringContainsString("\$table->id();", $migrationContent);
        $this->assertStringContainsString("\$table->timestamps();", $migrationContent);
        $this->assertStringContainsString("Schema::dropIfExists('products');", $migrationContent);
    }

    /**
     * Test migration with table update structure
     */
    #[Test]
    public function it_generates_table_update_migration_structure(): void
    {
        $this->runEasyModulesCommand('make-migration', 'Blog', 'AddStatusToPosts', ['--table' => 'posts']);

        $migrationFiles   = glob($this->testBasePath('Blog/Database/Migrations/*_add_status_to_posts.php'));
        $migrationContent = file_get_contents($migrationFiles[0]);

        // Should contain table update structure
        $this->assertStringContainsString("Schema::table('posts', function (Blueprint \$table) {", $migrationContent);
        $this->assertStringContainsString('//', $migrationContent); // Contains comment placeholders
    }

    /**
     * Test that generated migrations have proper Laravel 12 structure
     */
    #[Test]
    public function it_generates_proper_laravel_12_migration_structure(): void
    {
        $this->runEasyModulesCommand('make-migration', 'Blog', 'CreateStructureTestTable');

        $migrationFiles   = glob($this->testBasePath('Blog/Database/Migrations/*_create_structure_test_table.php'));
        $migrationContent = file_get_contents($migrationFiles[0]);

        // Laravel 12 specific structure
        $this->assertStringContainsString('<?php', $migrationContent);
        $this->assertStringContainsString('use Illuminate\Database\Migrations\Migration;', $migrationContent);
        $this->assertStringContainsString('use Illuminate\Database\Schema\Blueprint;', $migrationContent);
        $this->assertStringContainsString('use Illuminate\Support\Facades\Schema;', $migrationContent);
        $this->assertStringContainsString('return new class extends Migration', $migrationContent);
        $this->assertStringContainsString('public function up(): void', $migrationContent);
        $this->assertStringContainsString('public function down(): void', $migrationContent);
    }
}
