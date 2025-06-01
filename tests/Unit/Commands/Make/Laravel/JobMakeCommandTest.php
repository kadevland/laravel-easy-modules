<?php

declare(strict_types=1);

namespace Kadevland\EasyModules\Tests\Unit\Commands\Make\Laravel;

use Kadevland\EasyModules\Tests\Support\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Test suite for JobMakeCommand
 *
 * This command extends Laravel's base JobMakeCommand to generate
 * job classes within the modular structure, supporting both
 * queued and synchronous jobs.
 */
class JobMakeCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->setComponentPath('job', 'Infrastructure/Jobs');

        // Set app environment to testing to disable prompts
        $this->app['config']->set('app.env', 'testing');
    }

    protected function tearDown(): void
    {
        // Enhanced cleanup - remove all test files
        $testPaths = [
            'Blog/Infrastructure/Jobs',
            'Shop/Infrastructure/Jobs',
            'Test/Infrastructure/Jobs',
            'Custom/Domain/Jobs',
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
    // NIVEAU 5: BASIC JOB GENERATION TESTS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Test basic queued job file generation
     */
    #[Test]
    public function it_can_generate_basic_job_file(): void
    {
        $this->runEasyModulesCommand('make-job', 'Blog', 'ProcessPost');

        $this->assertModuleComponentExists('Blog', 'Infrastructure/Jobs', 'ProcessPost', [
            'use Illuminate\Contracts\Queue\ShouldQueue;',
            'use Illuminate\Foundation\Queue\Queueable;',
            'class ProcessPost implements ShouldQueue',
            'use Queueable;',
        ]);
    }

    /**
     * Test synchronous job file generation
     */
    #[Test]
    public function it_can_generate_sync_job_file(): void
    {
        $this->runEasyModulesCommand('make-job', 'Blog', 'ProcessSync', ['--sync' => true]);

        $this->assertModuleComponentExists('Blog', 'Infrastructure/Jobs', 'ProcessSync', [
            'use Illuminate\Foundation\Bus\Dispatchable;',
            'class ProcessSync',
            'use Dispatchable;',
        ]);

        // Should NOT contain queue-related imports for sync jobs
        $this->assertFileNotContains([
            'use Illuminate\Contracts\Queue\ShouldQueue;',
            'use Illuminate\Foundation\Queue\Queueable;',
        ], 'Blog/Infrastructure/Jobs/ProcessSync.php');
    }

    /**
     * Test job generation with different naming patterns
     */
    #[Test]
    public function it_can_generate_jobs_with_different_names(): void
    {
        $jobNames = [
            'ProcessPayment',
            'SendEmail',
            'GenerateReport',
            'CleanupFiles',
        ];

        foreach ($jobNames as $jobName) {
            $this->runEasyModulesCommand('make-job', 'Blog', $jobName);

            $this->assertFilenameExists("Blog/Infrastructure/Jobs/{$jobName}.php");
            $this->assertFileContains([
                'namespace App\\Modules\\Blog\\Infrastructure\\Jobs;',
                'use Illuminate\\Contracts\\Queue\\ShouldQueue;',
                "class {$jobName} implements ShouldQueue",
            ], "Blog/Infrastructure/Jobs/{$jobName}.php");
        }
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // NIVEAU 4: NAMESPACE CORRECTNESS TESTS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Test job namespace generation is correct
     */
    #[Test]
    public function it_generates_correct_job_namespace(): void
    {
        $this->runEasyModulesCommand('make-job', 'Blog', 'NamespaceTest');

        // Job should have correct namespace
        $this->assertFileContains([
            'namespace App\\Modules\\Blog\\Infrastructure\\Jobs;',
        ], 'Blog/Infrastructure/Jobs/NamespaceTest.php');

        // Should NOT contain duplicated namespace segments
        $this->assertFileNotContains([
            'namespace App\\Modules\\Blog\\Infrastructure\\Jobs\\Infrastructure\\Jobs;',
            'namespace App\\Modules\\Blog\\Blog\\Infrastructure\\Jobs;',
        ], 'Blog/Infrastructure/Jobs/NamespaceTest.php');
    }

    /**
     * Test job structure is properly generated
     */
    #[Test]
    public function it_generates_correct_job_structure(): void
    {
        $this->runEasyModulesCommand('make-job', 'Blog', 'StructureTest');

        $this->assertFileContains([
            'use Illuminate\\Foundation\\Queue\\Queueable;',
            'class StructureTest implements ShouldQueue',
            'public function handle()',
            'use Queueable;',
            'public function __construct()',
        ], 'Blog/Infrastructure/Jobs/StructureTest.php');
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // NIVEAU 3: DOUBLE PATH PREVENTION TESTS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Test prevention of double path and namespace duplication
     *
     * This test ensures that neither file paths nor namespaces are duplicated
     * when generating jobs within the module structure.
     */
    #[Test]
    public function it_prevents_double_path_and_namespace_duplication(): void
    {
        $this->runEasyModulesCommand('make-job', 'Blog', 'PathTest');

        // ✅ File path verification
        $this->assertFilenameExists('Blog/Infrastructure/Jobs/PathTest.php');
        $this->assertFilenameNotExists('Blog/Infrastructure/Jobs/Infrastructure/Jobs/PathTest.php');

        // ✅ Namespace duplication prevention
        $this->assertFileNotContains([
            'App\\Modules\\Blog\\Infrastructure\\Jobs\\Infrastructure\\Jobs',
            'App\\Modules\\Blog\\Blog\\Infrastructure\\Jobs',
            'Infrastructure\\Jobs\\Infrastructure\\Jobs',
        ], 'Blog/Infrastructure/Jobs/PathTest.php');
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // NIVEAU 2: CUSTOM CONFIGURATION TESTS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Test job generation with nested names
     */
    #[Test]
    public function it_can_generate_nested_jobs(): void
    {
        $this->runEasyModulesCommand('make-job', 'Blog', 'Email/SendNewsletter');

        // Should create nested directory structure
        $this->assertFilenameExists('Blog/Infrastructure/Jobs/Email/SendNewsletter.php');

        $this->assertFileContains([
            'namespace App\\Modules\\Blog\\Infrastructure\\Jobs\\Email;',
            'class SendNewsletter implements ShouldQueue',
        ], 'Blog/Infrastructure/Jobs/Email/SendNewsletter.php');
    }

    /**
     * Test deeply nested job generation
     */
    #[Test]
    public function it_handles_deeply_nested_jobs(): void
    {
        $this->runEasyModulesCommand('make-job', 'Blog', 'Reports/Admin/GenerateUserReport');

        $this->assertFilenameExists('Blog/Infrastructure/Jobs/Reports/Admin/GenerateUserReport.php');

        $this->assertFileContains([
            'namespace App\\Modules\\Blog\\Infrastructure\\Jobs\\Reports\\Admin;',
            'class GenerateUserReport implements ShouldQueue',
        ], 'Blog/Infrastructure/Jobs/Reports/Admin/GenerateUserReport.php');

        // Should not contain path duplication in deeply nested structure
        $this->assertFileNotContains([
            'namespace App\\Modules\\Blog\\Infrastructure\\Jobs\\Reports\\Admin\\Infrastructure\\Jobs\\Reports\\Admin;',
        ], 'Blog/Infrastructure/Jobs/Reports/Admin/GenerateUserReport.php');
    }

    /**
     * Test job generation with custom module configuration
     */
    #[Test]
    public function it_handles_custom_module_configurations(): void
    {
        // Test with custom base namespace and paths
        $this->app['config']->set('easymodules.base_namespace', 'Custom\\Modules');
        $this->app['config']->set('easymodules.paths.job', 'Domain/Jobs');

        $this->runEasyModulesCommand('make-job', 'Shop', 'ProcessOrder');

        // Check custom paths are used
        $this->assertFilenameExists('Shop/Domain/Jobs/ProcessOrder.php');

        // Check custom namespaces
        $this->assertFileContains([
            'namespace Custom\\Modules\\Shop\\Domain\\Jobs;',
            'class ProcessOrder implements ShouldQueue',
        ], 'Shop/Domain/Jobs/ProcessOrder.php');

        // Should NOT contain double namespace with custom config
        $this->assertFileNotContains([
            'namespace Custom\\Modules\\Shop\\Domain\\Jobs\\Domain\\Jobs;',
        ], 'Shop/Domain/Jobs/ProcessOrder.php');
    }

    /**
     * Test job generation with different module structures
     */
    #[Test]
    public function it_handles_different_module_structures(): void
    {
        $testConfigs = [
            ['namespace' => 'App\\Components', 'jobPath' => 'Jobs'],
            ['namespace' => 'Modules', 'jobPath' => 'Background/Jobs'],
            ['namespace' => 'Custom\\App\\Modules', 'jobPath' => 'Infrastructure/Jobs'],
        ];

        foreach ($testConfigs as $index => $config) {
            $this->app['config']->set('easymodules.base_namespace', $config['namespace']);
            $this->app['config']->set('easymodules.paths.job', $config['jobPath']);

            $jobName = "Test{$index}Job";

            $this->runEasyModulesCommand('make-job', 'Test', $jobName);

            $expectedJobPath = "Test/{$config['jobPath']}/{$jobName}.php";
            $this->assertFilenameExists($expectedJobPath);

            $expectedJobNamespace = str_replace('/', '\\', "{$config['namespace']}\\Test\\{$config['jobPath']}");
            $this->assertFileContains([
                "namespace {$expectedJobNamespace};",
                "class {$jobName}",
            ], $expectedJobPath);

            // Should not contain duplicated path segments
            $pathSegments = explode('/', $config['jobPath']);

            $duplicatedPatterns = [];
            foreach ($pathSegments as $segment) {
                $duplicatedPatterns[] = "{$expectedJobNamespace}\\{$segment}\\{$segment}";
            }

            $this->assertFileNotContains($duplicatedPatterns, $expectedJobPath);
        }
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // NIVEAU 1: EDGE CASE & INTEGRATION TESTS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Test sync option behavior
     */
    #[Test]
    public function it_handles_sync_option_correctly(): void
    {
        // Regular job should be queued
        $this->runEasyModulesCommand('make-job', 'Blog', 'RegularJob');

        $this->assertFileContains([
            'implements ShouldQueue',
            'use Queueable;',
        ], 'Blog/Infrastructure/Jobs/RegularJob.php');

        // Sync job should NOT be queued
        $this->runEasyModulesCommand('make-job', 'Blog', 'SyncJob', ['--sync' => true]);

        $this->assertFileNotContains([
            'implements ShouldQueue',
            'use Queueable;',
        ], 'Blog/Infrastructure/Jobs/SyncJob.php');

        $this->assertFileContains([
            'use Dispatchable;',
        ], 'Blog/Infrastructure/Jobs/SyncJob.php');
    }

    /**
     * Test suffix configuration behavior
     */
    #[Test]
    public function it_handles_suffix_configuration(): void
    {
        // Enable suffix appending
        $this->app['config']->set('easymodules.append_suffix', true);
        $this->app['config']->set('easymodules.suffixes.job', 'Job');

        // Test suffix addition when missing
        $this->runEasyModulesCommand('make-job', 'Blog', 'ProcessData');

        $this->assertFilenameExists('Blog/Infrastructure/Jobs/ProcessDataJob.php');
        $this->assertFileContains([
            'class ProcessDataJob implements ShouldQueue',
        ], 'Blog/Infrastructure/Jobs/ProcessDataJob.php');

        // Test suffix not duplicated when already present
        $this->runEasyModulesCommand('make-job', 'Blog', 'ProcessEmailJob');

        $this->assertFilenameExists('Blog/Infrastructure/Jobs/ProcessEmailJob.php');
        $this->assertFileContains([
            'class ProcessEmailJob implements ShouldQueue',
        ], 'Blog/Infrastructure/Jobs/ProcessEmailJob.php');

        // Should NOT create double suffix
        $this->assertFilenameNotExists('Blog/Infrastructure/Jobs/ProcessEmailJobJob.php');
    }

    /**
     * Test job generation with complex names
     */
    #[Test]
    public function it_handles_complex_job_names(): void
    {
        $complexCases = [
            'ProcessUserRegistration',
            'SendNewsletterEmail',
            'GenerateMonthlyReport',
            'CleanupExpiredSessions',
        ];

        foreach ($complexCases as $jobName) {
            $this->runEasyModulesCommand('make-job', 'Test', $jobName);

            $this->assertFilenameExists("Test/Infrastructure/Jobs/{$jobName}.php");

            $this->assertFileContains([
                "class {$jobName} implements ShouldQueue",
                'namespace App\\Modules\\Test\\Infrastructure\\Jobs;',
            ], "Test/Infrastructure/Jobs/{$jobName}.php");
        }
    }

    /**
     * Test job generation edge cases
     */
    #[Test]
    public function it_handles_edge_cases_gracefully(): void
    {
        // Test with single character names
        $this->runEasyModulesCommand('make-job', 'Blog', 'AJob');

        $this->assertFilenameExists('Blog/Infrastructure/Jobs/AJob.php');
        $this->assertFileContains([
            'class AJob implements ShouldQueue',
        ], 'Blog/Infrastructure/Jobs/AJob.php');

        // Test with numbers in names
        $this->runEasyModulesCommand('make-job', 'Blog', 'Process2Factor');

        $this->assertFilenameExists('Blog/Infrastructure/Jobs/Process2Factor.php');
        $this->assertFileContains([
            'class Process2Factor implements ShouldQueue',
        ], 'Blog/Infrastructure/Jobs/Process2Factor.php');
    }

    /**
     * Test job works with various module structures
     */
    #[Test]
    public function it_works_with_various_module_structures(): void
    {
        // Test multiple modules
        $modules = ['Blog', 'Shop', 'Admin'];

        foreach ($modules as $module) {
            $this->runEasyModulesCommand('make-job', $module, 'TestJob');

            $this->assertFilenameExists("{$module}/Infrastructure/Jobs/TestJob.php");

            $this->assertFileContains([
                "namespace App\\Modules\\{$module}\\Infrastructure\\Jobs;",
                'class TestJob implements ShouldQueue',
            ], "{$module}/Infrastructure/Jobs/TestJob.php");
        }
    }

    /**
     * Test multiple jobs in same module
     */
    #[Test]
    public function it_handles_multiple_jobs_in_same_module(): void
    {
        $jobs = [
            'ProcessPayment',
            'SendEmail',
            'GenerateReport',
            'Email/SendNewsletter',
        ];

        foreach ($jobs as $jobPath) {
            $this->runEasyModulesCommand('make-job', 'Blog', $jobPath);

            $expectedFile = "Blog/Infrastructure/Jobs/{$jobPath}.php";
            $this->assertFilenameExists($expectedFile);
        }

        // Verify all files exist and have correct content
        foreach ($jobs as $jobPath) {
            $expectedFile = "Blog/Infrastructure/Jobs/{$jobPath}.php";
            $className    = basename($jobPath);
            $this->assertFileContains([
                "class {$className} implements ShouldQueue",
            ], $expectedFile);
        }
    }

    /**
     * Test that generated jobs have proper structure
     */
    #[Test]
    public function it_generates_proper_job_structure(): void
    {
        $this->runEasyModulesCommand('make-job', 'Blog', 'StructureTestJob');

        $this->assertFileContains([
            'use Illuminate\\Contracts\\Queue\\ShouldQueue;',
            'use Illuminate\\Foundation\\Queue\\Queueable;',
            'class StructureTestJob implements ShouldQueue',
            'use Queueable;',
            'public function __construct()',
            'public function handle()',
        ], 'Blog/Infrastructure/Jobs/StructureTestJob.php');
    }
}
