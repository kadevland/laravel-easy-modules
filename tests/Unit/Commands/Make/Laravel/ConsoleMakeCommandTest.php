<?php

declare(strict_types=1);

namespace Kadevland\EasyModules\Tests\Unit\Commands\Make\Laravel;

use Kadevland\EasyModules\Tests\Support\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Test suite for ConsoleMakeCommand
 *
 * This command extends Laravel's base ConsoleMakeCommand to generate
 * console command classes within the modular structure, supporting
 * custom command signatures and descriptions.
 */
class ConsoleMakeCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->setComponentPath('command', 'Presentation/Console/Commands');

        // Set app environment to testing to disable prompts
        $this->app['config']->set('app.env', 'testing');
    }

    protected function tearDown(): void
    {
        // Enhanced cleanup - remove all test files
        $testPaths = [
            'Blog/Presentation/Console/Commands',
            'Shop/Presentation/Console/Commands',
            'Test/Presentation/Console/Commands',
            'Custom/Console/Commands',
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
    // NIVEAU 5: BASIC COMMAND GENERATION TESTS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Test basic console command file generation
     */
    #[Test]
    public function it_can_generate_basic_console_command(): void
    {
        $this->runEasyModulesCommand('make-command', 'Blog', 'ProcessPostsCommand');

        $this->assertFileContains([
            'namespace App\\Modules\\Blog\\Presentation\\Console\\Commands;',
            'use Illuminate\\Console\\Command;',
            'class ProcessPostsCommand extends Command',
            "protected \$signature = 'blog:process-posts-command';",
            'public function handle()',
        ], 'Blog/Presentation/Console/Commands/ProcessPostsCommand.php');
    }

    /**
     * Test console command generation with custom command option
     */
    #[Test]
    public function it_can_generate_command_with_custom_signature(): void
    {
        $this->runEasyModulesCommand('make-command', 'Blog', 'FooCommand', ['--command' => 'foo:bar']);

        $this->assertFileContains([
            'class FooCommand extends Command',
            "protected \$signature = 'foo:bar';",
            'public function handle()',
        ], 'Blog/Presentation/Console/Commands/FooCommand.php');
    }

    /**
     * Test command generation with different naming patterns
     */
    #[Test]
    public function it_can_generate_commands_with_different_names(): void
    {
        $commandNames = [
            'ProcessDataCommand',
            'SendEmailsCommand',
            'CleanupFilesCommand',
            'GenerateReportCommand',
        ];

        foreach ($commandNames as $commandName) {
            $this->runEasyModulesCommand('make-command', 'Blog', $commandName);

            $this->assertFilenameExists("Blog/Presentation/Console/Commands/{$commandName}.php");
            $this->assertFileContains([
                'namespace App\\Modules\\Blog\\Presentation\\Console\\Commands;',
                'use Illuminate\\Console\\Command;',
                "class {$commandName} extends Command",
            ], "Blog/Presentation/Console/Commands/{$commandName}.php");
        }
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // NIVEAU 4: NAMESPACE CORRECTNESS TESTS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Test command namespace generation is correct
     */
    #[Test]
    public function it_generates_correct_command_namespace(): void
    {
        $this->runEasyModulesCommand('make-command', 'Blog', 'TestCommand');

        $this->assertFileContains([
            'namespace App\\Modules\\Blog\\Presentation\\Console\\Commands;',
            'class TestCommand extends Command',
        ], 'Blog/Presentation/Console/Commands/TestCommand.php');
    }

    /**
     * Test command structure is properly generated
     */
    #[Test]
    public function it_generates_correct_command_structure(): void
    {
        $this->runEasyModulesCommand('make-command', 'Blog', 'StructureTestCommand');

        $this->assertFileContains([
            'use Illuminate\\Console\\Command;',
            'class StructureTestCommand extends Command',
            'protected $signature',
            'protected $description',
            'public function handle()',
        ], 'Blog/Presentation/Console/Commands/StructureTestCommand.php');
    }

    /**
     * Test nested command generation
     */
    #[Test]
    public function it_can_generate_nested_commands(): void
    {
        $this->runEasyModulesCommand('make-command', 'Blog', 'Admin/ProcessUsersCommand');

        $this->assertFilenameExists('Blog/Presentation/Console/Commands/Admin/ProcessUsersCommand.php');
        $this->assertFileContains([
            'namespace App\\Modules\\Blog\\Presentation\\Console\\Commands\\Admin;',
            'class ProcessUsersCommand extends Command',
        ], 'Blog/Presentation/Console/Commands/Admin/ProcessUsersCommand.php');
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // NIVEAU 3: DOUBLE PATH PREVENTION TESTS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Test prevention of double path and namespace duplication
     *
     * This test ensures that neither file paths nor namespaces are duplicated
     * when generating commands within the module structure.
     */
    #[Test]
    public function it_prevents_double_path_and_namespace_duplication(): void
    {
        $this->runEasyModulesCommand('make-command', 'Blog', 'PathTestCommand');

        // File path verification
        $this->assertFilenameExists('Blog/Presentation/Console/Commands/PathTestCommand.php');
        $this->assertFilenameNotExists('Blog/Presentation/Console/Commands/Presentation/Console/Commands/PathTestCommand.php');

        // Namespace duplication prevention
        $this->assertFileNotContains([
            'App\\Modules\\Blog\\Presentation\\Console\\Commands\\Presentation\\Console\\Commands',
            'App\\Modules\\Blog\\Blog\\Presentation\\Console\\Commands',
            'Presentation\\Console\\Commands\\Presentation\\Console\\Commands',
        ], 'Blog/Presentation/Console/Commands/PathTestCommand.php');
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // NIVEAU 2: CUSTOM CONFIGURATION TESTS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Test command generation with custom module configuration
     */
    #[Test]
    public function it_handles_custom_module_configurations(): void
    {
        // Test with custom base namespace and paths
        $this->app['config']->set('easymodules.base_namespace', 'Custom\\Modules');
        $this->app['config']->set('easymodules.paths.command', 'Console/Commands');

        $this->runEasyModulesCommand('make-command', 'Shop', 'ProcessOrdersCommand');

        // Check custom paths are used
        $this->assertFilenameExists('Shop/Console/Commands/ProcessOrdersCommand.php');

        // Check custom namespaces
        $this->assertFileContains([
            'namespace Custom\\Modules\\Shop\\Console\\Commands;',
            'class ProcessOrdersCommand extends Command',
        ], 'Shop/Console/Commands/ProcessOrdersCommand.php');

        // Should NOT contain double namespace with custom config
        $this->assertFileNotContains([
            'Custom\\Modules\\Shop\\Console\\Commands\\Console\\Commands',
        ], 'Shop/Console/Commands/ProcessOrdersCommand.php');
    }

    /**
     * Test command generation with different module structures
     */
    #[Test]
    public function it_handles_different_module_structures(): void
    {
        $testConfigs = [
            ['namespace' => 'App\\Components', 'commandPath' => 'Commands'],
            ['namespace' => 'Custom\\App\\Modules', 'commandPath' => 'Cli/Commands'],
        ];

        foreach ($testConfigs as $index => $config) {
            $this->app['config']->set('easymodules.base_namespace', $config['namespace']);
            $this->app['config']->set('easymodules.paths.command', $config['commandPath']);

            $commandName = "Test{$index}Command";

            $this->runEasyModulesCommand('make-command', 'Test', $commandName);

            $expectedCommandPath = "Test/{$config['commandPath']}/{$commandName}.php";
            $this->assertFilenameExists($expectedCommandPath);

            $expectedCommandNamespace = str_replace('/', '\\', "{$config['namespace']}\\Test\\{$config['commandPath']}");
            $this->assertFileContains([
                "namespace {$expectedCommandNamespace};",
                "class {$commandName} extends Command",
            ], $expectedCommandPath);
        }
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // NIVEAU 1: EDGE CASE & INTEGRATION TESTS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Test command signature generation patterns
     */
    #[Test]
    public function it_generates_correct_command_signatures(): void
    {
        $commandCases = [
            ['name' => 'ProcessDataCommand', 'expected' => 'blog:process-data-command'],
            ['name' => 'SendEmailsCommand', 'expected' => 'blog:send-emails-command'],
            ['name' => 'CleanupCommand', 'expected' => 'blog:cleanup-command'],
        ];

        foreach ($commandCases as $case) {
            $this->runEasyModulesCommand('make-command', 'Blog', $case['name']);

            $this->assertFileContains([
                "protected \$signature = '{$case['expected']}';",
            ], "Blog/Presentation/Console/Commands/{$case['name']}.php");
        }
    }

    /**
     * Test command generation with complex names
     */
    #[Test]
    public function it_handles_complex_command_names(): void
    {
        $complexCases = [
            'ProcessUserDataCommand',
            'GenerateMonthlyReportCommand',
            'CleanupExpiredSessionsCommand',
        ];

        foreach ($complexCases as $commandName) {
            $this->runEasyModulesCommand('make-command', 'Test', $commandName);

            $this->assertFilenameExists("Test/Presentation/Console/Commands/{$commandName}.php");
            $this->assertFileContains([
                "class {$commandName} extends Command",
                'namespace App\\Modules\\Test\\Presentation\\Console\\Commands;',
            ], "Test/Presentation/Console/Commands/{$commandName}.php");
        }
    }

    /**
     * Test command generation edge cases
     */
    #[Test]
    public function it_handles_edge_cases_gracefully(): void
    {
        // Test with single character names
        $this->runEasyModulesCommand('make-command', 'Blog', 'ACommand');

        $this->assertFilenameExists('Blog/Presentation/Console/Commands/ACommand.php');
        $this->assertFileContains([
            'class ACommand extends Command',
        ], 'Blog/Presentation/Console/Commands/ACommand.php');
    }

    /**
     * Test command works with various module structures
     */
    #[Test]
    public function it_works_with_various_module_structures(): void
    {
        $modules = ['Blog', 'Shop', 'Admin'];

        foreach ($modules as $module) {
            $this->runEasyModulesCommand('make-command', $module, 'TestCommand');

            $this->assertFilenameExists("{$module}/Presentation/Console/Commands/TestCommand.php");
            $this->assertFileContains([
                "namespace App\\Modules\\{$module}\\Presentation\\Console\\Commands;",
                'class TestCommand extends Command',
            ], "{$module}/Presentation/Console/Commands/TestCommand.php");
        }
    }

    /**
     * Test multiple commands in same module
     */
    #[Test]
    public function it_handles_multiple_commands_in_same_module(): void
    {
        $commands = [
            'ProcessDataCommand',
            'SendEmailsCommand',
            'Admin/ManageUsersCommand',
        ];

        foreach ($commands as $commandPath) {
            $this->runEasyModulesCommand('make-command', 'Blog', $commandPath);

            $expectedFile = "Blog/Presentation/Console/Commands/{$commandPath}.php";
            $this->assertFilenameExists($expectedFile);
        }

        // Verify all files exist and have correct content
        foreach ($commands as $commandPath) {
            $expectedFile = "Blog/Presentation/Console/Commands/{$commandPath}.php";
            $className    = basename($commandPath);
            $this->assertFileContains([
                "class {$className} extends Command",
            ], $expectedFile);
        }
    }
}
