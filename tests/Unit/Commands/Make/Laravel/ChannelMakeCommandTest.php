<?php

declare(strict_types=1);

namespace Kadevland\EasyModules\Tests\Unit\Commands\Make\Laravel;

use Kadevland\EasyModules\Tests\Support\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Test suite for ChannelMakeCommand
 *
 * This command extends Laravel's base ChannelMakeCommand to generate
 * broadcast channel authorization classes within the modular structure.
 */
class ChannelMakeCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->setComponentPath('channel', 'Infrastructure/Broadcasting');

        // Set app environment to testing to disable prompts
        $this->app['config']->set('app.env', 'testing');
    }

    protected function tearDown(): void
    {
        // Enhanced cleanup - remove all test files
        $testPaths = [
            'Blog/Infrastructure/Broadcasting',
            'Shop/Infrastructure/Broadcasting',
            'Test/Infrastructure/Broadcasting',
            'Custom/Domain/Broadcasting',
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
    // NIVEAU 5: BASIC CHANNEL GENERATION TESTS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Test basic channel file generation
     */
    #[Test]
    public function it_can_generate_basic_channel_file(): void
    {
        $this->runEasyModulesCommand('make-channel', 'Blog', 'PostChannel');

        $this->assertModuleComponentExists('Blog', 'Infrastructure/Broadcasting', 'PostChannel', [
            'use Illuminate\Foundation\Auth\User;',
            'class PostChannel',
        ]);
    }

    /**
     * Test channel generation with different naming patterns
     */
    #[Test]
    public function it_can_generate_channels_with_different_names(): void
    {
        $channelNames = [
            'UserChannel',
            'PostChannel',
            'CommentChannel',
            'NotificationChannel',
        ];

        foreach ($channelNames as $channelName) {
            $this->runEasyModulesCommand('make-channel', 'Blog', $channelName);

            $this->assertFilenameExists("Blog/Infrastructure/Broadcasting/{$channelName}.php");
            $this->assertFileContains([
                'namespace App\\Modules\\Blog\\Infrastructure\\Broadcasting;',
                'use Illuminate\\Foundation\\Auth\\User;',
                "class {$channelName}",
            ], "Blog/Infrastructure/Broadcasting/{$channelName}.php");
        }
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // NIVEAU 4: NAMESPACE CORRECTNESS TESTS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Test channel namespace generation is correct
     */
    #[Test]
    public function it_generates_correct_channel_namespace(): void
    {
        $this->runEasyModulesCommand('make-channel', 'Blog', 'NamespaceTest');

        // Channel should have correct namespace
        $this->assertFileContains([
            'namespace App\\Modules\\Blog\\Infrastructure\\Broadcasting;',
        ], 'Blog/Infrastructure/Broadcasting/NamespaceTest.php');

        // Should NOT contain duplicated namespace segments
        $this->assertFileNotContains([
            'namespace App\\Modules\\Blog\\Infrastructure\\Broadcasting\\Infrastructure\\Broadcasting;',
            'namespace App\\Modules\\Blog\\Blog\\Infrastructure\\Broadcasting;',
        ], 'Blog/Infrastructure/Broadcasting/NamespaceTest.php');
    }

    /**
     * Test channel structure is properly generated
     */
    #[Test]
    public function it_generates_correct_channel_structure(): void
    {
        $this->runEasyModulesCommand('make-channel', 'Blog', 'StructureTest');

        $this->assertFileContains([
            'use Illuminate\\Foundation\\Auth\\User;',
            'class StructureTest',
        ], 'Blog/Infrastructure/Broadcasting/StructureTest.php');
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // NIVEAU 3: DOUBLE PATH PREVENTION TESTS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Test prevention of double path and namespace duplication
     *
     * This test ensures that neither file paths nor namespaces are duplicated
     * when generating channels within the module structure.
     */
    #[Test]
    public function it_prevents_double_path_and_namespace_duplication(): void
    {
        $this->runEasyModulesCommand('make-channel', 'Blog', 'PathTest');

        // ✅ File path verification
        $this->assertFilenameExists('Blog/Infrastructure/Broadcasting/PathTest.php');
        $this->assertFilenameNotExists('Blog/Infrastructure/Broadcasting/Infrastructure/Broadcasting/PathTest.php');

        // ✅ Namespace duplication prevention
        $this->assertFileNotContains([
            'App\\Modules\\Blog\\Infrastructure\\Broadcasting\\Infrastructure\\Broadcasting',
            'App\\Modules\\Blog\\Blog\\Infrastructure\\Broadcasting',
            'Infrastructure\\Broadcasting\\Infrastructure\\Broadcasting',
        ], 'Blog/Infrastructure/Broadcasting/PathTest.php');
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // NIVEAU 2: CUSTOM CONFIGURATION TESTS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Test channel generation with nested names
     */
    #[Test]
    public function it_can_generate_nested_channels(): void
    {
        $this->runEasyModulesCommand('make-channel', 'Blog', 'Private/UserChannel');

        // Should create nested directory structure
        $this->assertFilenameExists('Blog/Infrastructure/Broadcasting/Private/UserChannel.php');

        $this->assertFileContains([
            'namespace App\\Modules\\Blog\\Infrastructure\\Broadcasting\\Private;',
            'class UserChannel',
        ], 'Blog/Infrastructure/Broadcasting/Private/UserChannel.php');
    }

    /**
     * Test deeply nested channel generation
     */
    #[Test]
    public function it_handles_deeply_nested_channels(): void
    {
        $this->runEasyModulesCommand('make-channel', 'Blog', 'Admin/Private/UserChannel');

        $this->assertFilenameExists('Blog/Infrastructure/Broadcasting/Admin/Private/UserChannel.php');

        $this->assertFileContains([
            'namespace App\\Modules\\Blog\\Infrastructure\\Broadcasting\\Admin\\Private;',
            'class UserChannel',
        ], 'Blog/Infrastructure/Broadcasting/Admin/Private/UserChannel.php');

        // Should not contain path duplication in deeply nested structure
        $this->assertFileNotContains([
            'namespace App\\Modules\\Blog\\Infrastructure\\Broadcasting\\Admin\\Private\\Infrastructure\\Broadcasting\\Admin\\Private;',
        ], 'Blog/Infrastructure/Broadcasting/Admin/Private/UserChannel.php');
    }

    /**
     * Test channel generation with custom module configuration
     */
    #[Test]
    public function it_handles_custom_module_configurations(): void
    {
        // Test with custom base namespace and paths
        $this->app['config']->set('easymodules.base_namespace', 'Custom\\Modules');
        $this->app['config']->set('easymodules.paths.channel', 'Domain/Broadcasting');

        $this->runEasyModulesCommand('make-channel', 'Shop', 'OrderChannel');

        // Check custom paths are used
        $this->assertFilenameExists('Shop/Domain/Broadcasting/OrderChannel.php');

        // Check custom namespaces
        $this->assertFileContains([
            'namespace Custom\\Modules\\Shop\\Domain\\Broadcasting;',
            'class OrderChannel',
        ], 'Shop/Domain/Broadcasting/OrderChannel.php');

        // Should NOT contain double namespace with custom config
        $this->assertFileNotContains([
            'namespace Custom\\Modules\\Shop\\Domain\\Broadcasting\\Domain\\Broadcasting;',
        ], 'Shop/Domain/Broadcasting/OrderChannel.php');
    }

    /**
     * Test channel generation with different module structures
     */
    #[Test]
    public function it_handles_different_module_structures(): void
    {
        $testConfigs = [
            ['namespace' => 'App\\Components', 'channelPath' => 'Broadcasting'],
            ['namespace' => 'Modules', 'channelPath' => 'Events/Broadcasting'],
            ['namespace' => 'Custom\\App\\Modules', 'channelPath' => 'Infrastructure/Broadcasting'],
        ];

        foreach ($testConfigs as $index => $config) {
            $this->app['config']->set('easymodules.base_namespace', $config['namespace']);
            $this->app['config']->set('easymodules.paths.channel', $config['channelPath']);

            $channelName = "Test{$index}Channel";

            $this->runEasyModulesCommand('make-channel', 'Test', $channelName);

            $expectedChannelPath = "Test/{$config['channelPath']}/{$channelName}.php";
            $this->assertFilenameExists($expectedChannelPath);

            $expectedChannelNamespace = str_replace('/', '\\', "{$config['namespace']}\\Test\\{$config['channelPath']}");
            $this->assertFileContains([
                "namespace {$expectedChannelNamespace};",
                "class {$channelName}",
            ], $expectedChannelPath);

            // Should not contain duplicated path segments
            $pathSegments = explode('/', $config['channelPath']);

            $duplicatedPatterns = [];
            foreach ($pathSegments as $segment) {
                $duplicatedPatterns[] = "{$expectedChannelNamespace}\\{$segment}\\{$segment}";
            }

            $this->assertFileNotContains($duplicatedPatterns, $expectedChannelPath);
        }
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // NIVEAU 1: EDGE CASE & INTEGRATION TESTS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Test channel generation with complex names
     */
    #[Test]
    public function it_handles_complex_channel_names(): void
    {
        $complexCases = [
            'UserNotificationChannel',
            'BlogPostCommentChannel',
            'AdminDashboardChannel',
            'PrivateMessageChannel',
        ];

        foreach ($complexCases as $channelName) {
            $this->runEasyModulesCommand('make-channel', 'Test', $channelName);

            $this->assertFilenameExists("Test/Infrastructure/Broadcasting/{$channelName}.php");

            $this->assertFileContains([
                "class {$channelName}",
                'namespace App\\Modules\\Test\\Infrastructure\\Broadcasting;',
            ], "Test/Infrastructure/Broadcasting/{$channelName}.php");
        }
    }

    /**
     * Test channel generation edge cases
     */
    #[Test]
    public function it_handles_edge_cases_gracefully(): void
    {
        // Test with single character names
        $this->runEasyModulesCommand('make-channel', 'Blog', 'AChannel');

        $this->assertFilenameExists('Blog/Infrastructure/Broadcasting/AChannel.php');

        $this->assertFileContains([
            'class AChannel',
        ], 'Blog/Infrastructure/Broadcasting/AChannel.php');

        // Test with numbers in names
        $this->runEasyModulesCommand('make-channel', 'Blog', 'User2Channel');

        $this->assertFilenameExists('Blog/Infrastructure/Broadcasting/User2Channel.php');

        $this->assertFileContains([
            'class User2Channel',
        ], 'Blog/Infrastructure/Broadcasting/User2Channel.php');
    }

    /**
     * Test channel works with various module structures
     */
    #[Test]
    public function it_works_with_various_module_structures(): void
    {
        // Test multiple modules
        $modules = ['Blog', 'Shop', 'Admin'];

        foreach ($modules as $module) {
            $this->runEasyModulesCommand('make-channel', $module, 'TestChannel');

            $this->assertFilenameExists("{$module}/Infrastructure/Broadcasting/TestChannel.php");

            $this->assertFileContains([
                "namespace App\\Modules\\{$module}\\Infrastructure\\Broadcasting;",
                'class TestChannel',
            ], "{$module}/Infrastructure/Broadcasting/TestChannel.php");
        }
    }

    /**
     * Test multiple channels in same module
     */
    #[Test]
    public function it_handles_multiple_channels_in_same_module(): void
    {
        $channels = [
            'UserChannel',
            'PostChannel',
            'CommentChannel',
            'Private/AdminChannel',
        ];

        foreach ($channels as $channelPath) {
            $this->runEasyModulesCommand('make-channel', 'Blog', $channelPath);

            $expectedFile = "Blog/Infrastructure/Broadcasting/{$channelPath}.php";
            $this->assertFilenameExists($expectedFile);
        }

        // Verify all files exist and have correct content
        foreach ($channels as $channelPath) {
            $expectedFile = "Blog/Infrastructure/Broadcasting/{$channelPath}.php";
            $className    = basename($channelPath);
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
        $this->app['config']->set('easymodules.suffixes.channel', 'Channel');

        // Test suffix addition when missing
        $this->runEasyModulesCommand('make-channel', 'Blog', 'User');

        $this->assertFilenameExists('Blog/Infrastructure/Broadcasting/UserChannel.php');
        $this->assertFileContains([
            'class UserChannel',
        ], 'Blog/Infrastructure/Broadcasting/UserChannel.php');

        // Test suffix not duplicated when already present
        $this->runEasyModulesCommand('make-channel', 'Blog', 'PostChannel');

        $this->assertFilenameExists('Blog/Infrastructure/Broadcasting/PostChannel.php');
        $this->assertFileContains([
            'class PostChannel',
        ], 'Blog/Infrastructure/Broadcasting/PostChannel.php');

        // Should NOT create double suffix
        $this->assertFilenameNotExists('Blog/Infrastructure/Broadcasting/PostChannelChannel.php');
    }
}
