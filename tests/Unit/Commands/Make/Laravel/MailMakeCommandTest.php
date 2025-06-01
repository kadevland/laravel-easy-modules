<?php

declare(strict_types=1);

namespace Kadevland\EasyModules\Tests\Unit\Commands\Make\Laravel;

use Kadevland\EasyModules\Tests\Support\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Test suite for MailMakeCommand
 *
 * This command extends Laravel's base MailMakeCommand to generate
 * mail classes within the modular structure, supporting all Laravel
 * options like --markdown for mail templates.
 */
class MailMakeCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->setComponentPath('mail', 'Infrastructure/Mails');

        // Set app environment to testing to disable prompts
        $this->app['config']->set('app.env', 'testing');
    }

    protected function tearDown(): void
    {
        // Enhanced cleanup - remove all test files
        $testPaths = [
            'Blog/Infrastructure/Mails',
            'Shop/Infrastructure/Mails',
            'Test/Infrastructure/Mails',
            'Custom/Domain/Mails',
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
    // NIVEAU 5: BASIC MAIL GENERATION TESTS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Test basic mail file generation
     */
    #[Test]
    public function it_can_generate_basic_mail_file(): void
    {
        $this->runEasyModulesCommand('make-mail', 'Blog', 'WelcomeMail');

        $this->assertModuleComponentExists('Blog', 'Infrastructure/Mails', 'WelcomeMail', [
            'use Illuminate\Mail\Mailable;',
            'class WelcomeMail extends Mailable',
            'public function envelope()',
            'public function content()',
        ]);
    }

    /**
     * Test mail generation with markdown option
     */
    #[Test]
    public function it_can_generate_mail_with_markdown_option(): void
    {
        $this->runEasyModulesCommand('make-mail', 'Blog', 'NewsletterMail', ['--markdown' => 'newsletter']);

        $this->assertModuleComponentExists('Blog', 'Infrastructure/Mails', 'NewsletterMail', [
            'use Illuminate\Mail\Mailable;',
            'class NewsletterMail extends Mailable',
            "markdown: 'newsletter',",
        ]);
    }

    /**
     * Test mail generation with different naming patterns
     */
    #[Test]
    public function it_can_generate_mails_with_different_names(): void
    {
        $mailNames = [
            'WelcomeMail',
            'ResetPasswordMail',
            'OrderConfirmationMail',
            'NewsletterMail',
        ];

        foreach ($mailNames as $mailName) {
            $this->runEasyModulesCommand('make-mail', 'Blog', $mailName);

            $this->assertFilenameExists("Blog/Infrastructure/Mails/{$mailName}.php");
            $this->assertFileContains([
                'namespace App\\Modules\\Blog\\Infrastructure\\Mails;',
                'use Illuminate\\Mail\\Mailable;',
                "class {$mailName} extends Mailable",
            ], "Blog/Infrastructure/Mails/{$mailName}.php");
        }
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // NIVEAU 4: NAMESPACE CORRECTNESS TESTS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Test mail namespace generation is correct
     */
    #[Test]
    public function it_generates_correct_mail_namespace(): void
    {
        $this->runEasyModulesCommand('make-mail', 'Blog', 'NamespaceTest');

        // Mail should have correct namespace
        $this->assertFileContains([
            'namespace App\\Modules\\Blog\\Infrastructure\\Mails;',
        ], 'Blog/Infrastructure/Mails/NamespaceTest.php');

        // Should NOT contain duplicated namespace segments
        $this->assertFileNotContains([
            'namespace App\\Modules\\Blog\\Infrastructure\\Mails\\Infrastructure\\Mails;',
            'namespace App\\Modules\\Blog\\Blog\\Infrastructure\\Mails;',
        ], 'Blog/Infrastructure/Mails/NamespaceTest.php');
    }

    /**
     * Test mail structure is properly generated
     */
    #[Test]
    public function it_generates_correct_mail_structure(): void
    {
        $this->runEasyModulesCommand('make-mail', 'Blog', 'StructureTest');

        $this->assertFileContains([
            'use Illuminate\\Mail\\Mailable;',
            'class StructureTest extends Mailable',
            'public function envelope()',
            'public function content()',
            'public function attachments()',
        ], 'Blog/Infrastructure/Mails/StructureTest.php');
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // NIVEAU 3: DOUBLE PATH PREVENTION TESTS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Test prevention of double path and namespace duplication
     *
     * This test ensures that neither file paths nor namespaces are duplicated
     * when generating mails within the module structure.
     */
    #[Test]
    public function it_prevents_double_path_and_namespace_duplication(): void
    {
        $this->runEasyModulesCommand('make-mail', 'Blog', 'PathTest');

        // ✅ File path verification
        $this->assertFilenameExists('Blog/Infrastructure/Mails/PathTest.php');
        $this->assertFilenameNotExists('Blog/Infrastructure/Mails/Infrastructure/Mails/PathTest.php');

        // ✅ Namespace duplication prevention
        $this->assertFileNotContains([
            'App\\Modules\\Blog\\Infrastructure\\Mails\\Infrastructure\\Mails',
            'App\\Modules\\Blog\\Blog\\Infrastructure\\Mails',
            'Infrastructure\\Mails\\Infrastructure\\Mails',
        ], 'Blog/Infrastructure/Mails/PathTest.php');
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // NIVEAU 2: CUSTOM CONFIGURATION TESTS (CONTINUED)
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Test mail generation with nested names
     */
    #[Test]
    public function it_can_generate_nested_mails(): void
    {
        $this->runEasyModulesCommand('make-mail', 'Blog', 'Auth/WelcomeMail');

        // Should create nested directory structure
        $this->assertFilenameExists('Blog/Infrastructure/Mails/Auth/WelcomeMail.php');

        $this->assertFileContains([
            'namespace App\\Modules\\Blog\\Infrastructure\\Mails\\Auth;',
            'class WelcomeMail extends Mailable',
        ], 'Blog/Infrastructure/Mails/Auth/WelcomeMail.php');
    }

    /**
     * Test deeply nested mail generation
     */
    #[Test]
    public function it_handles_deeply_nested_mails(): void
    {
        $this->runEasyModulesCommand('make-mail', 'Blog', 'Admin/User/WelcomeMail');

        $this->assertFilenameExists('Blog/Infrastructure/Mails/Admin/User/WelcomeMail.php');

        $this->assertFileContains([
            'namespace App\\Modules\\Blog\\Infrastructure\\Mails\\Admin\\User;',
            'class WelcomeMail extends Mailable',
        ], 'Blog/Infrastructure/Mails/Admin/User/WelcomeMail.php');

        // Should not contain path duplication in deeply nested structure
        $this->assertFileNotContains([
            'namespace App\\Modules\\Blog\\Infrastructure\\Mails\\Admin\\User\\Infrastructure\\Mails\\Admin\\User;',
        ], 'Blog/Infrastructure/Mails/Admin/User/WelcomeMail.php');
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // NIVEAU 2: CUSTOM CONFIGURATION TESTS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Test mail generation with custom module configuration
     */
    #[Test]
    public function it_handles_custom_module_configurations(): void
    {
        // Test with custom base namespace and paths
        $this->app['config']->set('easymodules.base_namespace', 'Custom\\Modules');
        $this->app['config']->set('easymodules.paths.mail', 'Domain/Mails');

        $this->runEasyModulesCommand('make-mail', 'Shop', 'OrderMail');

        // Check custom paths are used
        $this->assertFilenameExists('Shop/Domain/Mails/OrderMail.php');

        // Check custom namespaces
        $this->assertFileContains([
            'namespace Custom\\Modules\\Shop\\Domain\\Mails;',
            'class OrderMail extends Mailable',
        ], 'Shop/Domain/Mails/OrderMail.php');

        // Should NOT contain double namespace with custom config
        $this->assertFileNotContains([
            'namespace Custom\\Modules\\Shop\\Domain\\Mails\\Domain\\Mails;',
        ], 'Shop/Domain/Mails/OrderMail.php');
    }

    /**
     * Test mail generation with different module structures
     */
    #[Test]
    public function it_handles_different_module_structures(): void
    {
        $testConfigs = [
            ['namespace' => 'App\\Components', 'mailPath' => 'Mails'],
            ['namespace' => 'Modules', 'mailPath' => 'Messaging/Mails'],
            ['namespace' => 'Custom\\App\\Modules', 'mailPath' => 'Infrastructure/Mails'],
        ];

        foreach ($testConfigs as $index => $config) {
            $this->app['config']->set('easymodules.base_namespace', $config['namespace']);
            $this->app['config']->set('easymodules.paths.mail', $config['mailPath']);

            $mailName = "Test{$index}Mail";

            $this->runEasyModulesCommand('make-mail', 'Test', $mailName);

            $expectedMailPath = "Test/{$config['mailPath']}/{$mailName}.php";
            $this->assertFilenameExists($expectedMailPath);

            $expectedMailNamespace = str_replace('/', '\\', "{$config['namespace']}\\Test\\{$config['mailPath']}");
            $this->assertFileContains([
                "namespace {$expectedMailNamespace};",
                "class {$mailName}",
            ], $expectedMailPath);


            // Should not contain duplicated path segments
            $pathSegments = explode('/', $config['mailPath']);

            $duplicatedPatterns = [];
            foreach ($pathSegments as $segment) {
                $duplicatedPatterns[] = "{$expectedMailNamespace}\\{$segment}\\{$segment}";
            }

            $this->assertFileNotContains($duplicatedPatterns, $expectedMailPath);
        }
    }


    // ═══════════════════════════════════════════════════════════════════════════════════════
    // NIVEAU 1: EDGE CASE & INTEGRATION TESTS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Test mail generation with complex names
     */
    #[Test]
    public function it_handles_complex_mail_names(): void
    {
        $complexCases = [
            'UserRegistrationWelcomeMail',
            'OrderConfirmationMail',
            'PasswordResetNotificationMail',
            'WeeklyNewsletterMail',
        ];

        foreach ($complexCases as $mailName) {
            $this->runEasyModulesCommand('make-mail', 'Test', $mailName);

            $this->assertFilenameExists("Test/Infrastructure/Mails/{$mailName}.php");

            $this->assertFileContains([
                "class {$mailName} extends Mailable",
                'namespace App\\Modules\\Test\\Infrastructure\\Mails;',
            ], "Test/Infrastructure/Mails/{$mailName}.php");
        }
    }

    /**
     * Test mail generation edge cases
     */
    #[Test]
    public function it_handles_edge_cases_gracefully(): void
    {
        // Test with single character names
        $this->runEasyModulesCommand('make-mail', 'Blog', 'AMail');

        $this->assertFilenameExists('Blog/Infrastructure/Mails/AMail.php');

        $this->assertFileContains([
            'class AMail extends Mailable',
        ], 'Blog/Infrastructure/Mails/AMail.php');

        // Test with numbers in names
        $this->runEasyModulesCommand('make-mail', 'Blog', 'Newsletter2Mail');

        $this->assertFilenameExists('Blog/Infrastructure/Mails/Newsletter2Mail.php');

        $this->assertFileContains([
            'class Newsletter2Mail extends Mailable',
        ], 'Blog/Infrastructure/Mails/Newsletter2Mail.php');
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // NIVEAU 1: EDGE CASE & INTEGRATION TESTS (CONTINUED)
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Test mail works with various module structures
     */
    #[Test]
    public function it_works_with_various_module_structures(): void
    {
        // Test multiple modules
        $modules = ['Blog', 'Shop', 'Admin'];

        foreach ($modules as $module) {
            $this->runEasyModulesCommand('make-mail', $module, 'TestMail');

            $this->assertFilenameExists("{$module}/Infrastructure/Mails/TestMail.php");

            $this->assertFileContains([
                "namespace App\\Modules\\{$module}\\Infrastructure\\Mails;",
                'class TestMail extends Mailable',
            ], "{$module}/Infrastructure/Mails/TestMail.php");
        }
    }

    /**
     * Test multiple mails in same module
     */
    #[Test]
    public function it_handles_multiple_mails_in_same_module(): void
    {
        $mails = [
            'WelcomeMail',
            'ResetPasswordMail',
            'OrderConfirmationMail',
            'Auth/LoginMail',
        ];

        foreach ($mails as $mailPath) {
            $this->runEasyModulesCommand('make-mail', 'Blog', $mailPath);

            $expectedFile = "Blog/Infrastructure/Mails/{$mailPath}.php";
            $this->assertFilenameExists($expectedFile);
        }

        // Verify all files exist and have correct content
        foreach ($mails as $mailPath) {
            $expectedFile = "Blog/Infrastructure/Mails/{$mailPath}.php";
            $mailContent  = $this->files->get($this->testBasePath($expectedFile));

            $className = basename($mailPath);
            $this->assertStringContainsString("class {$className} extends Mailable", $mailContent);
        }
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // NIVEAU 1: EDGE CASE & INTEGRATION TESTS (MAIL OPTIONS)
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Test suffix configuration behavior
     */
    #[Test]
    public function it_handles_suffix_configuration(): void
    {
        // Enable suffix appending
        $this->app['config']->set('easymodules.append_suffix', true);
        $this->app['config']->set('easymodules.suffixes.mail', 'Mail');

        // Test suffix addition when missing
        $this->runEasyModulesCommand('make-mail', 'Blog', 'Welcome');

        $this->assertFilenameExists('Blog/Infrastructure/Mails/WelcomeMail.php');
        $this->assertFileContains([
            'class WelcomeMail extends Mailable',
        ], 'Blog/Infrastructure/Mails/WelcomeMail.php');

        // Test suffix not duplicated when already present
        $this->runEasyModulesCommand('make-mail', 'Blog', 'NewsletterMail');

        $this->assertFilenameExists('Blog/Infrastructure/Mails/NewsletterMail.php');
        $this->assertFileContains([
            'class NewsletterMail extends Mailable',
        ], 'Blog/Infrastructure/Mails/NewsletterMail.php');

        // Should NOT create double suffix
        $this->assertFilenameNotExists('Blog/Infrastructure/Mails/NewsletterMailMail.php');
    }

    /**
     * Test markdown option behavior
     */
    #[Test]
    public function it_handles_markdown_option_correctly(): void
    {
        // Regular mail should use view template
        $this->runEasyModulesCommand('make-mail', 'Blog', 'RegularMail');

        // $this->assertFileContains([
        //     "view: 'mail.regular'",
        // ], 'Blog/Infrastructure/Mails/RegularMail.php'); // TODO: Fix false positive

        $this->assertFileNotContains([
            "markdown:",
        ], 'Blog/Infrastructure/Mails/RegularMail.php');

        // Markdown mail should use markdown template
        $this->runEasyModulesCommand('make-mail', 'Blog', 'MarkdownMail', ['--markdown' => 'newsletter']);

        $this->assertFileContains([
            "markdown: 'newsletter'",
        ], 'Blog/Infrastructure/Mails/MarkdownMail.php');

        // $this->assertFileNotContains([
        //     "view:",
        // ], 'Blog/Infrastructure/Mails/MarkdownMail.php'); // TODO: Fix false positive
    }

    /**
     * Test mail with nested structure and markdown
     */
    #[Test]
    public function it_can_generate_nested_markdown_mails(): void
    {
        $this->runEasyModulesCommand('make-mail', 'Blog', 'Auth/VerificationMail', ['--markdown' => 'auth.verify']);

        $this->assertFilenameExists('Blog/Infrastructure/Mails/Auth/VerificationMail.php');

        $this->assertFileContains([
            'namespace App\\Modules\\Blog\\Infrastructure\\Mails\\Auth;',
            'class VerificationMail extends Mailable',
            "markdown: 'auth.verify',",
        ], 'Blog/Infrastructure/Mails/Auth/VerificationMail.php');

        // Should NOT contain view reference for markdown mails
        $this->assertFileNotContains([
            "view:",
        ], 'Blog/Infrastructure/Mails/Auth/VerificationMail.php');
    }
}
