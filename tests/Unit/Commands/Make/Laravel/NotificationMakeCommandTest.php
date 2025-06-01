<?php

declare(strict_types=1);

namespace Kadevland\EasyModules\Tests\Unit\Commands\Make\Laravel;

use Kadevland\EasyModules\Tests\Support\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Test suite for NotificationMakeCommand
 *
 * This command extends Laravel's base NotificationMakeCommand to generate
 * notification classes within the modular structure, supporting all Laravel
 * options like --markdown for notification templates.
 */
class NotificationMakeCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->setComponentPath('notification', 'Infrastructure/Notifications');

        // Set app environment to testing to disable prompts
        $this->app['config']->set('app.env', 'testing');
    }

    protected function tearDown(): void
    {
        // Enhanced cleanup - remove all test files
        $testPaths = [
            'Blog/Infrastructure/Notifications',
            'Shop/Infrastructure/Notifications',
            'Test/Infrastructure/Notifications',
            'Custom/Domain/Notifications',
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
    // NIVEAU 5: BASIC NOTIFICATION GENERATION TESTS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Test basic notification file generation
     */
    #[Test]
    public function it_can_generate_basic_notification_file(): void
    {
        $this->runEasyModulesCommand('make-notification', 'Blog', 'WelcomeNotification');

        $this->assertModuleComponentExists('Blog', 'Infrastructure/Notifications', 'WelcomeNotification', [
            'use Illuminate\Notifications\Notification;',
            'class WelcomeNotification extends Notification',
            'return (new MailMessage)',
        ]);
    }

    /**
     * Test notification generation with markdown option
     */
    #[Test]
    public function it_can_generate_notification_with_markdown_option(): void
    {
        $this->runEasyModulesCommand('make-notification', 'Blog', 'NewsletterNotification', ['--markdown' => 'newsletter-notification']);

        $this->assertModuleComponentExists('Blog', 'Infrastructure/Notifications', 'NewsletterNotification', [
            'use Illuminate\Notifications\Notification;',
            'class NewsletterNotification extends Notification',
            "return (new MailMessage)->markdown('newsletter-notification')",
        ]);
    }

    /**
     * Test notification generation with different naming patterns
     */
    #[Test]
    public function it_can_generate_notifications_with_different_names(): void
    {
        $notificationNames = [
            'WelcomeNotification',
            'PasswordResetNotification',
            'OrderConfirmationNotification',
            'NewsletterNotification',
        ];

        foreach ($notificationNames as $notificationName) {
            $this->runEasyModulesCommand('make-notification', 'Blog', $notificationName);

            $this->assertFilenameExists("Blog/Infrastructure/Notifications/{$notificationName}.php");
            $this->assertFileContains([
                'namespace App\\Modules\\Blog\\Infrastructure\\Notifications;',
                'use Illuminate\\Notifications\\Notification;',
                "class {$notificationName} extends Notification",
            ], "Blog/Infrastructure/Notifications/{$notificationName}.php");
        }
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // NIVEAU 4: NAMESPACE CORRECTNESS TESTS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Test notification namespace generation is correct
     */
    #[Test]
    public function it_generates_correct_notification_namespace(): void
    {
        $this->runEasyModulesCommand('make-notification', 'Blog', 'NamespaceTest');

        // Notification should have correct namespace
        $this->assertFileContains([
            'namespace App\\Modules\\Blog\\Infrastructure\\Notifications;',
        ], 'Blog/Infrastructure/Notifications/NamespaceTest.php');

        // Should NOT contain duplicated namespace segments
        $this->assertFileNotContains([
            'namespace App\\Modules\\Blog\\Infrastructure\\Notifications\\Infrastructure\\Notifications;',
            'namespace App\\Modules\\Blog\\Blog\\Infrastructure\\Notifications;',
        ], 'Blog/Infrastructure/Notifications/NamespaceTest.php');
    }

    /**
     * Test notification structure is properly generated
     */
    #[Test]
    public function it_generates_correct_notification_structure(): void
    {
        $this->runEasyModulesCommand('make-notification', 'Blog', 'StructureTest');

        $this->assertFileContains([
            'use Illuminate\\Notifications\\Notification;',
            'class StructureTest extends Notification',
            'public function via(object $notifiable)',
            'public function toMail(object $notifiable)',
        ], 'Blog/Infrastructure/Notifications/StructureTest.php');
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // NIVEAU 3: DOUBLE PATH PREVENTION TESTS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Test prevention of double path and namespace duplication
     *
     * This test ensures that neither file paths nor namespaces are duplicated
     * when generating notifications within the module structure.
     */
    #[Test]
    public function it_prevents_double_path_and_namespace_duplication(): void
    {
        $this->runEasyModulesCommand('make-notification', 'Blog', 'PathTest');

        // File path verification
        $this->assertFilenameExists('Blog/Infrastructure/Notifications/PathTest.php');
        $this->assertFilenameNotExists('Blog/Infrastructure/Notifications/Infrastructure/Notifications/PathTest.php');

        // Namespace duplication prevention
        $this->assertFileNotContains([
            'App\\Modules\\Blog\\Infrastructure\\Notifications\\Infrastructure\\Notifications',
            'App\\Modules\\Blog\\Blog\\Infrastructure\\Notifications',
            'Infrastructure\\Notifications\\Infrastructure\\Notifications',
        ], 'Blog/Infrastructure/Notifications/PathTest.php');
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // NIVEAU 2: CUSTOM CONFIGURATION TESTS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Test notification generation with nested names
     */
    #[Test]
    public function it_can_generate_nested_notifications(): void
    {
        $this->runEasyModulesCommand('make-notification', 'Blog', 'Auth/WelcomeNotification');

        // Should create nested directory structure
        $this->assertFilenameExists('Blog/Infrastructure/Notifications/Auth/WelcomeNotification.php');

        $this->assertFileContains([
            'namespace App\\Modules\\Blog\\Infrastructure\\Notifications\\Auth;',
            'class WelcomeNotification extends Notification',
        ], 'Blog/Infrastructure/Notifications/Auth/WelcomeNotification.php');
    }

    /**
     * Test deeply nested notification generation
     */
    #[Test]
    public function it_handles_deeply_nested_notifications(): void
    {
        $this->runEasyModulesCommand('make-notification', 'Blog', 'Admin/User/WelcomeNotification');

        $this->assertFilenameExists('Blog/Infrastructure/Notifications/Admin/User/WelcomeNotification.php');

        $this->assertFileContains([
            'namespace App\\Modules\\Blog\\Infrastructure\\Notifications\\Admin\\User;',
            'class WelcomeNotification extends Notification',
        ], 'Blog/Infrastructure/Notifications/Admin/User/WelcomeNotification.php');

        // Should not contain path duplication in deeply nested structure
        $this->assertFileNotContains([
            'namespace App\\Modules\\Blog\\Infrastructure\\Notifications\\Admin\\User\\Infrastructure\\Notifications\\Admin\\User;',
        ], 'Blog/Infrastructure/Notifications/Admin/User/WelcomeNotification.php');
    }

    /**
     * Test notification generation with custom module configuration
     */
    #[Test]
    public function it_handles_custom_module_configurations(): void
    {
        // Test with custom base namespace and paths
        $this->app['config']->set('easymodules.base_namespace', 'Custom\\Modules');
        $this->app['config']->set('easymodules.paths.notification', 'Domain/Notifications');

        $this->runEasyModulesCommand('make-notification', 'Shop', 'OrderNotification');

        // Check custom paths are used
        $this->assertFilenameExists('Shop/Domain/Notifications/OrderNotification.php');

        // Check custom namespaces
        $this->assertFileContains([
            'namespace Custom\\Modules\\Shop\\Domain\\Notifications;',
            'class OrderNotification extends Notification',
        ], 'Shop/Domain/Notifications/OrderNotification.php');

        // Should NOT contain double namespace with custom config
        $this->assertFileNotContains([
            'namespace Custom\\Modules\\Shop\\Domain\\Notifications\\Domain\\Notifications;',
        ], 'Shop/Domain/Notifications/OrderNotification.php');
    }

    /**
     * Test notification generation with different module structures
     */
    #[Test]
    public function it_handles_different_module_structures(): void
    {
        $testConfigs = [
            ['namespace' => 'App\\Components', 'notificationPath' => 'Notifications'],
            ['namespace' => 'Modules', 'notificationPath' => 'Messaging/Notifications'],
            ['namespace' => 'Custom\\App\\Modules', 'notificationPath' => 'Infrastructure/Notifications'],
        ];

        foreach ($testConfigs as $index => $config) {
            $this->app['config']->set('easymodules.base_namespace', $config['namespace']);
            $this->app['config']->set('easymodules.paths.notification', $config['notificationPath']);

            $notificationName = "Test{$index}Notification";

            $this->runEasyModulesCommand('make-notification', 'Test', $notificationName);

            $expectedNotificationPath = "Test/{$config['notificationPath']}/{$notificationName}.php";
            $this->assertFilenameExists($expectedNotificationPath);

            $expectedNotificationNamespace = str_replace('/', '\\', "{$config['namespace']}\\Test\\{$config['notificationPath']}");
            $this->assertFileContains([
                "namespace {$expectedNotificationNamespace};",
                "class {$notificationName}",
            ], $expectedNotificationPath);

            // Should not contain duplicated path segments
            $pathSegments = explode('/', $config['notificationPath']);

            $duplicatedPatterns = [];
            foreach ($pathSegments as $segment) {
                $duplicatedPatterns[] = "{$expectedNotificationNamespace}\\{$segment}\\{$segment}";
            }

            $this->assertFileNotContains($duplicatedPatterns, $expectedNotificationPath);
        }
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // NIVEAU 1: EDGE CASE & INTEGRATION TESTS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Test notification generation with complex names
     */
    #[Test]
    public function it_handles_complex_notification_names(): void
    {
        $complexCases = [
            'UserRegistrationWelcomeNotification',
            'OrderConfirmationNotification',
            'PasswordResetNotification',
            'WeeklyNewsletterNotification',
        ];

        foreach ($complexCases as $notificationName) {
            $this->runEasyModulesCommand('make-notification', 'Test', $notificationName);

            $this->assertFilenameExists("Test/Infrastructure/Notifications/{$notificationName}.php");

            $this->assertFileContains([
                "class {$notificationName} extends Notification",
                'namespace App\\Modules\\Test\\Infrastructure\\Notifications;',
            ], "Test/Infrastructure/Notifications/{$notificationName}.php");
        }
    }

    /**
     * Test notification generation edge cases
     */
    #[Test]
    public function it_handles_edge_cases_gracefully(): void
    {
        // Test with single character names
        $this->runEasyModulesCommand('make-notification', 'Blog', 'ANotification');

        $this->assertFilenameExists('Blog/Infrastructure/Notifications/ANotification.php');

        $this->assertFileContains([
            'class ANotification extends Notification',
        ], 'Blog/Infrastructure/Notifications/ANotification.php');

        // Test with numbers in names
        $this->runEasyModulesCommand('make-notification', 'Blog', 'Newsletter2Notification');

        $this->assertFilenameExists('Blog/Infrastructure/Notifications/Newsletter2Notification.php');

        $this->assertFileContains([
            'class Newsletter2Notification extends Notification',
        ], 'Blog/Infrastructure/Notifications/Newsletter2Notification.php');
    }

    /**
     * Test notification works with various module structures
     */
    #[Test]
    public function it_works_with_various_module_structures(): void
    {
        // Test multiple modules
        $modules = ['Blog', 'Shop', 'Admin'];

        foreach ($modules as $module) {
            $this->runEasyModulesCommand('make-notification', $module, 'TestNotification');

            $this->assertFilenameExists("{$module}/Infrastructure/Notifications/TestNotification.php");

            $this->assertFileContains([
                "namespace App\\Modules\\{$module}\\Infrastructure\\Notifications;",
                'class TestNotification extends Notification',
            ], "{$module}/Infrastructure/Notifications/TestNotification.php");
        }
    }

    /**
     * Test multiple notifications in same module
     */
    #[Test]
    public function it_handles_multiple_notifications_in_same_module(): void
    {
        $notifications = [
            'WelcomeNotification',
            'ResetPasswordNotification',
            'OrderConfirmationNotification',
            'Auth/LoginNotification',
        ];

        foreach ($notifications as $notificationPath) {
            $this->runEasyModulesCommand('make-notification', 'Blog', $notificationPath);

            $expectedFile = "Blog/Infrastructure/Notifications/{$notificationPath}.php";
            $this->assertFilenameExists($expectedFile);
        }

        // Verify all files exist and have correct content
        foreach ($notifications as $notificationPath) {
            $expectedFile = "Blog/Infrastructure/Notifications/{$notificationPath}.php";
            $className    = basename($notificationPath);
            $this->assertFileContains([
                "class {$className} extends Notification",
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
        $this->app['config']->set('easymodules.suffixes.notification', 'Notification');

        // Test suffix addition when missing
        $this->runEasyModulesCommand('make-notification', 'Blog', 'Welcome');

        $this->assertFilenameExists('Blog/Infrastructure/Notifications/WelcomeNotification.php');
        $this->assertFileContains([
            'class WelcomeNotification extends Notification',
        ], 'Blog/Infrastructure/Notifications/WelcomeNotification.php');

        // Test suffix not duplicated when already present
        $this->runEasyModulesCommand('make-notification', 'Blog', 'NewsletterNotification');

        $this->assertFilenameExists('Blog/Infrastructure/Notifications/NewsletterNotification.php');
        $this->assertFileContains([
            'class NewsletterNotification extends Notification',
        ], 'Blog/Infrastructure/Notifications/NewsletterNotification.php');

        // Should NOT create double suffix
        $this->assertFilenameNotExists('Blog/Infrastructure/Notifications/NewsletterNotificationNotification.php');
    }

    /**
     * Test markdown option behavior
     */
    #[Test]
    public function it_handles_markdown_option_correctly(): void
    {
        // Regular notification should use mail message
        $this->runEasyModulesCommand('make-notification', 'Blog', 'RegularNotification');

        $this->assertFileContains([
            'return (new MailMessage)',
        ], 'Blog/Infrastructure/Notifications/RegularNotification.php');

        // Markdown notification should use markdown template
        $this->runEasyModulesCommand('make-notification', 'Blog', 'MarkdownNotification', ['--markdown' => 'newsletter']);

        $this->assertFileContains([
            "return (new MailMessage)->markdown('newsletter')",
        ], 'Blog/Infrastructure/Notifications/MarkdownNotification.php');
    }

    /**
     * Test notification with nested structure and markdown
     */
    #[Test]
    public function it_can_generate_nested_markdown_notifications(): void
    {
        $this->runEasyModulesCommand('make-notification', 'Blog', 'Auth/VerificationNotification', ['--markdown' => 'auth.verify']);

        $this->assertFilenameExists('Blog/Infrastructure/Notifications/Auth/VerificationNotification.php');

        $this->assertFileContains([
            'namespace App\\Modules\\Blog\\Infrastructure\\Notifications\\Auth;',
            'class VerificationNotification extends Notification',
            "return (new MailMessage)->markdown('auth.verify')",
        ], 'Blog/Infrastructure/Notifications/Auth/VerificationNotification.php');
    }

    /**
     * Test that generated notifications have proper structure
     */
    #[Test]
    public function it_generates_proper_notification_structure(): void
    {
        $this->runEasyModulesCommand('make-notification', 'Blog', 'StructureTestNotification');

        $this->assertFileContains([
            'public function via(object $notifiable): array',
            'public function toMail(object $notifiable): MailMessage',
            'public function toArray(object $notifiable): array',
        ], 'Blog/Infrastructure/Notifications/StructureTestNotification.php');
    }
}
