<?php

declare(strict_types=1);

namespace Kadevland\EasyModules\Tests\Unit\Commands\Make\EasyModule;

use Kadevland\EasyModules\Tests\Support\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Test suite for StubMakeCommand
 *
 * This command provides flexible generation of any component type within
 * the modular structure based on configuration, supporting dynamic path
 * resolution and custom stub types with proper replacement handling.
 */
class StubMakeCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Set app environment to testing to disable prompts
        $this->app['config']->set('app.env', 'testing');

        // Configure only 2 random stub types for testing to avoid conflicts
        $this->setComponentPath('testcomponent', 'Test/Components');
        $this->setComponentStub('testcomponent', 'easymodules/testcomponent.stub');

        $this->setComponentPath('randomtype', 'Random/Types');
        $this->setComponentStub('randomtype', 'easymodules/randomtype.stub');
    }

    protected function tearDown(): void
    {
        // Enhanced cleanup - remove all test files
        $testPaths = [
            'Blog/Test/Components',
            'Blog/Random/Types',
            'Shop/Custom/Components',
            'Test/Services',
            'Test/Custom/TestComponents',
            'Admin/Random/Types',
            'Blog',
            'Shop',
            'Test',
            'Admin',
        ];

        foreach ($testPaths as $path) {
            $fullPath = $this->testBasePath($path);
            if ($this->files->isDirectory($fullPath)) {
                $this->files->deleteDirectory($fullPath, true);
            }
        }

        parent::tearDown();
    }

    /**
     * Create test stubs for component testing
     *
     * @return void
     */
    protected function createTestComponentStubs(): void
    {
        $baseTemplate = implode("\n", [
            '<?php',
            '',
            'declare(strict_types=1);',
            '',
            'namespace {{ namespace }};',
            '',
            'class {{ class }}',
            '{',
            '    // {{ stub }} component',
            '}',
        ]);

        $richTemplate = implode("\n", [
            '<?php',
            '',
            'declare(strict_types=1);',
            '',
            'namespace {{ namespace }};',
            '',
            '/**',
            ' * {{ class }} - {{ stub }} type',
            ' * Module: {{ module }}',
            ' */',
            'class {{ class }}',
            '{',
            '    // Generated {{ stub }}',
            '}',
        ]);

        $this->createTestStub('easymodules/testcomponent.stub', $richTemplate);
        $this->createTestStub('easymodules/randomtype.stub', $baseTemplate);
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // NIVEAU 5: BASIC STUB GENERATION TESTS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Test basic stub file generation with existing type
     */
    #[Test]
    public function it_can_generate_basic_stub_file(): void
    {
        $this->createTestComponentStubs();

        // Back to artisan for debugging - runEasyModulesCommand() has issues
        $this->artisan('easymodules:make-stub', [
            'module' => 'Blog',
            'name'   => 'TestClass',
            'stub'   => 'testcomponent',
        ])
            ->assertExitCode(0);

        $this->assertFilenameExists('Blog/Test/Components/TestClass.php');
    }

    /**
     * Test stub file replacements are applied correctly
     */
    #[Test]
    public function it_applies_stub_replacements_correctly(): void
    {
        $this->createTestComponentStubs();

        $this->artisan('easymodules:make-stub', [
            'module' => 'Blog',
            'name'   => 'MyClass',
            'stub'   => 'testcomponent',
        ])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace App\\Modules\\Blog\\Test\\Components;',
            'class MyClass',
            '* MyClass - Testcomponent type',
            'Module: Blog',
            '// Generated Testcomponent',
        ], 'Blog/Test/Components/MyClass.php');
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // NIVEAU 4: NAMESPACE CORRECTNESS TESTS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Test stub namespace generation is correct
     */
    #[Test]
    public function it_generates_correct_stub_namespace(): void
    {
        $this->createTestComponentStubs();

        $this->artisan('easymodules:make-stub', [
            'module' => 'Blog',
            'name'   => 'Post',
            'stub'   => 'randomtype',
        ])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace App\\Modules\\Blog\\Random\\Types;',
        ], 'Blog/Random/Types/Post.php');

        // Should NOT contain duplicated namespace segments
        $this->assertFileNotContains([
            'App\\Modules\\Blog\\Random\\Types\\Random\\Types',
            'App\\Modules\\Blog\\Blog\\Random\\Types',
        ], 'Blog/Random/Types/Post.php');
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // NIVEAU 3: DOUBLE PATH PREVENTION TESTS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Test prevention of double path and namespace duplication
     *
     * This test ensures that neither file paths nor namespaces are duplicated
     * when generating stubs within the module structure.
     */
    #[Test]
    public function it_prevents_double_path_and_namespace_duplication(): void
    {
        $this->createTestComponentStubs();

        $this->runEasyModulesCommand('make-stub', 'Blog', 'PathTest', ['stub' => 'testcomponent']);

        // File path verification
        $this->assertFilenameExists('Blog/Test/Components/PathTest.php');
        $this->assertFilenameNotExists('Blog/Test/Components/Test/Components/PathTest.php');

        // Namespace duplication prevention
        $this->assertFileNotContains([
            'App\\Modules\\Blog\\Test\\Components\\Test\\Components',
            'App\\Modules\\Blog\\Blog\\Test\\Components',
            'Test\\Components\\Test\\Components',
        ], 'Blog/Test/Components/PathTest.php');
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // NIVEAU 2: CUSTOM CONFIGURATION TESTS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Test fallback to root module namespace when no path configured
     */
    #[Test]
    public function it_uses_root_module_namespace_when_no_path_configured(): void
    {
        // Create a stub type that's not in the paths config but is valid for validation
        $this->setComponentStub('unknowntype', 'easymodules/unknowntype.stub');

        $stubContent = "<?php\n\nnamespace {{ namespace }};\n\nclass {{ class }}\n{\n}\n";
        $this->createTestStub('easymodules/unknowntype.stub', $stubContent);

        $this->runEasyModulesCommand('make-stub', 'Blog', 'UnknownStub', ['stub' => 'unknowntype']);

        // Should be placed in root module namespace since no path configured
        $this->assertFilenameExists('Blog/UnknownStub.php');
        $this->assertFileContains([
            'namespace App\\Modules\\Blog;',
            'class UnknownStub',
        ], 'Blog/UnknownStub.php');
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // NIVEAU 1: EDGE CASE & INTEGRATION TESTS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Test invalid stub type shows available stubs
     */
    #[Test]
    public function it_shows_available_stubs_for_invalid_type(): void
    {

        $this->artisan('easymodules:make-stub', [
            'module' => 'Blog',
            'name'   => 'TestClass',
            'stub'   => 'nonexistenttype',
        ])
            ->expectsOutputToContain('📋 Available Stub Types:')
            ->assertExitCode(1);
    }
}
