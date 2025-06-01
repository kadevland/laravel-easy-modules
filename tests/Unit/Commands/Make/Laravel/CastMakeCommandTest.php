<?php

declare(strict_types=1);

namespace Kadevland\EasyModules\Tests\Unit\Commands\Make\Laravel;

use Kadevland\EasyModules\Tests\Support\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Test suite for CastMakeCommand
 *
 * This command extends Laravel's base CastMakeCommand to generate
 * Eloquent cast classes within the modular structure, supporting
 * both regular casts and inbound-only casts.
 */
class CastMakeCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->setComponentPath('cast', 'Infrastructure/Casts');

        // Set app environment to testing to disable prompts
        $this->app['config']->set('app.env', 'testing');
    }

    protected function tearDown(): void
    {
        // Enhanced cleanup - remove all test files
        $testPaths = [
            'Blog/Infrastructure/Casts',
            'Shop/Infrastructure/Casts',
            'Test/Infrastructure/Casts',
            'Custom/Domain/Casts',
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
    // NIVEAU 5: BASIC CAST GENERATION TESTS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Test basic cast file generation
     */
    #[Test]
    public function it_can_generate_basic_cast_file(): void
    {
        $this->runEasyModulesCommand('make-cast', 'Blog', 'JsonCast');

        $this->assertModuleComponentExists('Blog', 'Infrastructure/Casts', 'JsonCast', [
            'use Illuminate\Contracts\Database\Eloquent\CastsAttributes;',
            'use Illuminate\Database\Eloquent\Model;',
            'class JsonCast implements CastsAttributes',
            'public function get(Model $model, string $key, mixed $value, array $attributes): mixed',
            'public function set(Model $model, string $key, mixed $value, array $attributes): mixed',
        ]);
    }

    /**
     * Test inbound cast file generation
     */
    #[Test]
    public function it_can_generate_inbound_cast_file(): void
    {
        $this->runEasyModulesCommand('make-cast', 'Blog', 'HashCast', ['--inbound' => true]);

        $this->assertModuleComponentExists('Blog', 'Infrastructure/Casts', 'HashCast', [
            'use Illuminate\Contracts\Database\Eloquent\CastsInboundAttributes;',
            'use Illuminate\Database\Eloquent\Model;',
            'class HashCast implements CastsInboundAttributes',
            'public function set(Model $model, string $key, mixed $value, array $attributes): mixed',
        ]);

        // Should NOT contain outbound get method for inbound casts
        $this->assertFileNotContains([
            'public function get(Model $model, string $key, mixed $value, array $attributes): mixed',
            'CastsAttributes',
        ], 'Blog/Infrastructure/Casts/HashCast.php');
    }

    /**
     * Test cast generation with different naming patterns
     */
    #[Test]
    public function it_can_generate_casts_with_different_names(): void
    {
        $castNames = [
            'JsonCast',
            'EncryptedCast',
            'TimestampCast',
            'CurrencyCast',
        ];

        foreach ($castNames as $castName) {
            $this->runEasyModulesCommand('make-cast', 'Blog', $castName);

            $this->assertFilenameExists("Blog/Infrastructure/Casts/{$castName}.php");
            $this->assertFileContains([
                'namespace App\\Modules\\Blog\\Infrastructure\\Casts;',
                'use Illuminate\\Contracts\\Database\\Eloquent\\CastsAttributes;',
                "class {$castName} implements CastsAttributes",
            ], "Blog/Infrastructure/Casts/{$castName}.php");
        }
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // NIVEAU 4: NAMESPACE CORRECTNESS TESTS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Test cast namespace generation is correct
     */
    #[Test]
    public function it_generates_correct_cast_namespace(): void
    {
        $this->runEasyModulesCommand('make-cast', 'Blog', 'NamespaceTest');

        // Cast should have correct namespace
        $this->assertFileContains([
            'namespace App\\Modules\\Blog\\Infrastructure\\Casts;',
        ], 'Blog/Infrastructure/Casts/NamespaceTest.php');

        // Should NOT contain duplicated namespace segments
        $this->assertFileNotContains([
            'namespace App\\Modules\\Blog\\Infrastructure\\Casts\\Infrastructure\\Casts;',
            'namespace App\\Modules\\Blog\\Blog\\Infrastructure\\Casts;',
        ], 'Blog/Infrastructure/Casts/NamespaceTest.php');
    }

    /**
     * Test cast methods are properly generated
     */
    #[Test]
    public function it_generates_correct_cast_methods(): void
    {
        $this->runEasyModulesCommand('make-cast', 'Blog', 'MethodTest');

        $this->assertFileContains([
            'public function get(Model $model, string $key, mixed $value, array $attributes): mixed',
            'public function set(Model $model, string $key, mixed $value, array $attributes): mixed',
        ], 'Blog/Infrastructure/Casts/MethodTest.php');
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // NIVEAU 3: DOUBLE PATH PREVENTION TESTS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Test prevention of double path and namespace duplication
     *
     * This test ensures that neither file paths nor namespaces are duplicated
     * when generating casts within the module structure.
     */
    #[Test]
    public function it_prevents_double_path_and_namespace_duplication(): void
    {
        $this->runEasyModulesCommand('make-cast', 'Blog', 'PathTest');

        //  File path verification
        $this->assertFilenameExists('Blog/Infrastructure/Casts/PathTest.php');
        $this->assertFilenameNotExists('Blog/Infrastructure/Casts/Infrastructure/Casts/PathTest.php');

        //  Namespace duplication prevention
        $this->assertFileNotContains([
            'App\\Modules\\Blog\\Infrastructure\\Casts\\Infrastructure\\Casts',
            'App\\Modules\\Blog\\Blog\\Infrastructure\\Casts',
            'Infrastructure\\Casts\\Infrastructure\\Casts',
        ], 'Blog/Infrastructure/Casts/PathTest.php');
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // NIVEAU 2: CUSTOM CONFIGURATION TESTS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Test cast generation with nested names
     */
    #[Test]
    public function it_can_generate_nested_casts(): void
    {
        $this->runEasyModulesCommand('make-cast', 'Blog', 'Serializers/JsonCast');

        // Should create nested directory structure
        $this->assertFilenameExists('Blog/Infrastructure/Casts/Serializers/JsonCast.php');

        $this->assertFileContains([
            'namespace App\\Modules\\Blog\\Infrastructure\\Casts\\Serializers;',
            'class JsonCast implements CastsAttributes',
        ], 'Blog/Infrastructure/Casts/Serializers/JsonCast.php');
    }

    /**
     * Test deeply nested cast generation
     */
    #[Test]
    public function it_handles_deeply_nested_casts(): void
    {
        $this->runEasyModulesCommand('make-cast', 'Blog', 'Data/Serializers/JsonCast');

        $this->assertFilenameExists('Blog/Infrastructure/Casts/Data/Serializers/JsonCast.php');

        $this->assertFileContains([
            'namespace App\\Modules\\Blog\\Infrastructure\\Casts\\Data\\Serializers;',
            'class JsonCast implements CastsAttributes',
        ], 'Blog/Infrastructure/Casts/Data/Serializers/JsonCast.php');

        // Should not contain path duplication in deeply nested structure
        $this->assertFileNotContains([
            'namespace App\\Modules\\Blog\\Infrastructure\\Casts\\Data\\Serializers\\Infrastructure\\Casts\\Data\\Serializers;',
        ], 'Blog/Infrastructure/Casts/Data/Serializers/JsonCast.php');
    }

    /**
     * Test cast generation with custom module configuration
     */
    #[Test]
    public function it_handles_custom_module_configurations(): void
    {
        // Test with custom base namespace and paths
        $this->app['config']->set('easymodules.base_namespace', 'Custom\\Modules');
        $this->app['config']->set('easymodules.paths.cast', 'Domain/Casts');

        $this->runEasyModulesCommand('make-cast', 'Shop', 'PriceCast');

        // Check custom paths are used
        $this->assertFilenameExists('Shop/Domain/Casts/PriceCast.php');

        // Check custom namespaces
        $this->assertFileContains([
            'namespace Custom\\Modules\\Shop\\Domain\\Casts;',
            'class PriceCast implements CastsAttributes',
        ], 'Shop/Domain/Casts/PriceCast.php');

        // Should NOT contain double namespace with custom config
        $this->assertFileNotContains([
            'namespace Custom\\Modules\\Shop\\Domain\\Casts\\Domain\\Casts;',
        ], 'Shop/Domain/Casts/PriceCast.php');
    }

    /**
     * Test cast generation with different module structures
     */
    #[Test]
    public function it_handles_different_module_structures(): void
    {
        $testConfigs = [
            ['namespace' => 'App\\Components', 'castPath' => 'Casts'],
            ['namespace' => 'Modules', 'castPath' => 'Data/Casts'],
            ['namespace' => 'Custom\\App\\Modules', 'castPath' => 'Infrastructure/Casts'],
        ];

        foreach ($testConfigs as $index => $config) {
            $this->app['config']->set('easymodules.base_namespace', $config['namespace']);
            $this->app['config']->set('easymodules.paths.cast', $config['castPath']);

            $castName = "Test{$index}Cast";

            $this->runEasyModulesCommand('make-cast', 'Test', $castName);

            $expectedCastPath = "Test/{$config['castPath']}/{$castName}.php";
            $this->assertFilenameExists($expectedCastPath);

            $expectedCastNamespace = str_replace('/', '\\', "{$config['namespace']}\\Test\\{$config['castPath']}");
            $this->assertFileContains([
                "namespace {$expectedCastNamespace};",
                "class {$castName}",
            ], $expectedCastPath);

            // Should not contain duplicated path segments
            $pathSegments = explode('/', $config['castPath']);

            $duplicatedPatterns = [];
            foreach ($pathSegments as $segment) {
                $duplicatedPatterns[] = "{$expectedCastNamespace}\\{$segment}\\{$segment}";
            }

            $this->assertFileNotContains($duplicatedPatterns, $expectedCastPath);
        }
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // NIVEAU 1: EDGE CASE & INTEGRATION TESTS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Test cast generation with complex names
     */
    #[Test]
    public function it_handles_complex_cast_names(): void
    {
        $complexCases = [
            'UserPreferencesCast',
            'BlogPostMetadataCast',
            'PaymentMethodCast',
            'JsonEncodedDataCast',
        ];

        foreach ($complexCases as $castName) {
            $this->runEasyModulesCommand('make-cast', 'Test', $castName);

            $this->assertFilenameExists("Test/Infrastructure/Casts/{$castName}.php");

            $this->assertFileContains([
                "class {$castName} implements CastsAttributes",
                'namespace App\\Modules\\Test\\Infrastructure\\Casts;',
            ], "Test/Infrastructure/Casts/{$castName}.php");
        }
    }

    /**
     * Test cast generation edge cases
     */
    #[Test]
    public function it_handles_edge_cases_gracefully(): void
    {
        // Test with single character names
        $this->runEasyModulesCommand('make-cast', 'Blog', 'ACast');

        $this->assertFilenameExists('Blog/Infrastructure/Casts/ACast.php');

        $this->assertFileContains([
            'class ACast implements CastsAttributes',
        ], 'Blog/Infrastructure/Casts/ACast.php');

        // Test with numbers in names
        $this->runEasyModulesCommand('make-cast', 'Blog', 'Json2Cast');

        $this->assertFilenameExists('Blog/Infrastructure/Casts/Json2Cast.php');

        $this->assertFileContains([
            'class Json2Cast implements CastsAttributes',
        ], 'Blog/Infrastructure/Casts/Json2Cast.php');
    }

    /**
     * Test cast works with various module structures
     */
    #[Test]
    public function it_works_with_various_module_structures(): void
    {
        // Test multiple modules
        $modules = ['Blog', 'Shop', 'Admin'];

        foreach ($modules as $module) {
            $this->runEasyModulesCommand('make-cast', $module, 'TestCast');

            $this->assertFilenameExists("{$module}/Infrastructure/Casts/TestCast.php");

            $this->assertFileContains([
                "namespace App\\Modules\\{$module}\\Infrastructure\\Casts;",
                'class TestCast implements CastsAttributes',
            ], "{$module}/Infrastructure/Casts/TestCast.php");
        }
    }

    /**
     * Test multiple casts in same module
     */
    #[Test]
    public function it_handles_multiple_casts_in_same_module(): void
    {
        $casts = [
            'JsonCast',
            'EncryptedCast',
            'HashCast',
            'Serializers/ArrayCast',
        ];

        foreach ($casts as $castPath) {
            $this->runEasyModulesCommand('make-cast', 'Blog', $castPath);

            $expectedFile = "Blog/Infrastructure/Casts/{$castPath}.php";
            $this->assertFilenameExists($expectedFile);
        }

        // Verify all files exist and have correct content
        foreach ($casts as $castPath) {
            $expectedFile = "Blog/Infrastructure/Casts/{$castPath}.php";
            $className    = basename($castPath);
            $this->assertFileContains([
                "class {$className} implements CastsAttributes",
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
        $this->app['config']->set('easymodules.suffixes.cast', 'Cast');

        // Test suffix addition when missing
        $this->runEasyModulesCommand('make-cast', 'Blog', 'Json');

        $this->assertFilenameExists('Blog/Infrastructure/Casts/JsonCast.php');
        $this->assertFileContains([
            'class JsonCast implements CastsAttributes',
        ], 'Blog/Infrastructure/Casts/JsonCast.php');

        // Test suffix not duplicated when already present
        $this->runEasyModulesCommand('make-cast', 'Blog', 'EncryptedCast');

        $this->assertFilenameExists('Blog/Infrastructure/Casts/EncryptedCast.php');
        $this->assertFileContains([
            'class EncryptedCast implements CastsAttributes',
        ], 'Blog/Infrastructure/Casts/EncryptedCast.php');

        // Should NOT create double suffix
        $this->assertFilenameNotExists('Blog/Infrastructure/Casts/EncryptedCastCast.php');
    }

    /**
     * Test inbound cast option behavior
     */
    #[Test]
    public function it_handles_inbound_option_correctly(): void
    {
        // Regular cast should have both get and set methods
        $this->runEasyModulesCommand('make-cast', 'Blog', 'RegularCast');

        $this->assertFileContains([
            'CastsAttributes',
            'public function get(',
            'public function set(',
        ], 'Blog/Infrastructure/Casts/RegularCast.php');

        // Inbound cast should only have set method
        $this->runEasyModulesCommand('make-cast', 'Blog', 'InboundCast', ['--inbound' => true]);

        $this->assertFileContains([
            'CastsInboundAttributes',
            'public function set(',
        ], 'Blog/Infrastructure/Casts/InboundCast.php');

        $this->assertFileNotContains([
            'public function get(',
        ], 'Blog/Infrastructure/Casts/InboundCast.php');
    }

    /**
     * Test inbound cast with nested structure
     */
    #[Test]
    public function it_can_generate_nested_inbound_casts(): void
    {
        $this->runEasyModulesCommand('make-cast', 'Blog', 'Security/HashCast', ['--inbound' => true]);

        $this->assertFilenameExists('Blog/Infrastructure/Casts/Security/HashCast.php');

        $this->assertFileContains([
            'namespace App\\Modules\\Blog\\Infrastructure\\Casts\\Security;',
            'use Illuminate\\Contracts\\Database\\Eloquent\\CastsInboundAttributes;',
            'class HashCast implements CastsInboundAttributes',
            'public function set(Model $model, string $key, mixed $value, array $attributes): mixed',
        ], 'Blog/Infrastructure/Casts/Security/HashCast.php');

        // Should NOT contain get method for inbound casts
        $this->assertFileNotContains([
            'public function get(Model $model, string $key, mixed $value, array $attributes): mixed',
        ], 'Blog/Infrastructure/Casts/Security/HashCast.php');
    }
}
