<?php

declare(strict_types=1);

namespace Kadevland\EasyModules\Tests\Unit\Commands\Make\Laravel;

use Kadevland\EasyModules\Tests\Support\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Test suite for EventMakeCommand
 *
 * This command extends Laravel's base EventMakeCommand to generate
 * event classes within the modular structure for event-driven architecture.
 */
class EventMakeCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->setComponentPath('event', 'Infrastructure/Events');

        // Set app environment to testing to disable prompts
        $this->app['config']->set('app.env', 'testing');
    }

    protected function tearDown(): void
    {
        // Enhanced cleanup - remove all test files
        $testPaths = [
            'Blog/Infrastructure/Events',
            'Shop/Infrastructure/Events',
            'Test/Infrastructure/Events',
            'Custom/Domain/Events',
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
    // NIVEAU 5: BASIC EVENT GENERATION TESTS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Test basic event file generation
     */
    #[Test]
    public function it_can_generate_basic_event_file(): void
    {
        $this->runEasyModulesCommand('make-event', 'Blog', 'PostCreated');

        $this->assertModuleComponentExists('Blog', 'Infrastructure/Events', 'PostCreated', [
            'class PostCreated',
        ]);
    }

    /**
     * Test event generation with different naming patterns
     */
    #[Test]
    public function it_can_generate_events_with_different_names(): void
    {
        $eventNames = [
            'UserRegistered',
            'PostCreated',
            'CommentDeleted',
            'OrderProcessed',
        ];

        foreach ($eventNames as $eventName) {
            $this->runEasyModulesCommand('make-event', 'Blog', $eventName);

            $this->assertFilenameExists("Blog/Infrastructure/Events/{$eventName}.php");
            $this->assertFileContains([
                'namespace App\\Modules\\Blog\\Infrastructure\\Events;',
                "class {$eventName}",
            ], "Blog/Infrastructure/Events/{$eventName}.php");
        }
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // NIVEAU 4: NAMESPACE CORRECTNESS TESTS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Test event namespace generation is correct
     */
    #[Test]
    public function it_generates_correct_event_namespace(): void
    {
        $this->runEasyModulesCommand('make-event', 'Blog', 'NamespaceTest');

        // Event should have correct namespace
        $this->assertFileContains([
            'namespace App\\Modules\\Blog\\Infrastructure\\Events;',
        ], 'Blog/Infrastructure/Events/NamespaceTest.php');

        // Should NOT contain duplicated namespace segments
        $this->assertFileNotContains([
            'namespace App\\Modules\\Blog\\Infrastructure\\Events\\Infrastructure\\Events;',
            'namespace App\\Modules\\Blog\\Blog\\Infrastructure\\Events;',
        ], 'Blog/Infrastructure/Events/NamespaceTest.php');
    }

    /**
     * Test event structure is properly generated
     */
    #[Test]
    public function it_generates_correct_event_structure(): void
    {
        $this->runEasyModulesCommand('make-event', 'Blog', 'StructureTest');

        $this->assertFileContains([
            'class StructureTest',
        ], 'Blog/Infrastructure/Events/StructureTest.php');
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // NIVEAU 3: DOUBLE PATH PREVENTION TESTS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Test prevention of double path and namespace duplication
     *
     * This test ensures that neither file paths nor namespaces are duplicated
     * when generating events within the module structure.
     */
    #[Test]
    public function it_prevents_double_path_and_namespace_duplication(): void
    {
        $this->runEasyModulesCommand('make-event', 'Blog', 'PathTest');

        // ✅ File path verification
        $this->assertFilenameExists('Blog/Infrastructure/Events/PathTest.php');
        $this->assertFilenameNotExists('Blog/Infrastructure/Events/Infrastructure/Events/PathTest.php');

        // ✅ Namespace duplication prevention
        $this->assertFileNotContains([
            'App\\Modules\\Blog\\Infrastructure\\Events\\Infrastructure\\Events',
            'App\\Modules\\Blog\\Blog\\Infrastructure\\Events',
            'Infrastructure\\Events\\Infrastructure\\Events',
        ], 'Blog/Infrastructure/Events/PathTest.php');
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // NIVEAU 2: CUSTOM CONFIGURATION TESTS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Test event generation with nested names
     */
    #[Test]
    public function it_can_generate_nested_events(): void
    {
        $this->runEasyModulesCommand('make-event', 'Blog', 'User/Registered');

        // Should create nested directory structure
        $this->assertFilenameExists('Blog/Infrastructure/Events/User/Registered.php');

        $this->assertFileContains([
            'namespace App\\Modules\\Blog\\Infrastructure\\Events\\User;',
            'class Registered',
        ], 'Blog/Infrastructure/Events/User/Registered.php');
    }

    /**
     * Test deeply nested event generation
     */
    #[Test]
    public function it_handles_deeply_nested_events(): void
    {
        $this->runEasyModulesCommand('make-event', 'Blog', 'User/Profile/Updated');

        $this->assertFilenameExists('Blog/Infrastructure/Events/User/Profile/Updated.php');

        $this->assertFileContains([
            'namespace App\\Modules\\Blog\\Infrastructure\\Events\\User\\Profile;',
            'class Updated',
        ], 'Blog/Infrastructure/Events/User/Profile/Updated.php');

        // Should not contain path duplication in deeply nested structure
        $this->assertFileNotContains([
            'namespace App\\Modules\\Blog\\Infrastructure\\Events\\User\\Profile\\Infrastructure\\Events\\User\\Profile;',
        ], 'Blog/Infrastructure/Events/User/Profile/Updated.php');
    }

    /**
     * Test event generation with custom module configuration
     */
    #[Test]
    public function it_handles_custom_module_configurations(): void
    {
        // Test with custom base namespace and paths
        $this->app['config']->set('easymodules.base_namespace', 'Custom\\Modules');
        $this->app['config']->set('easymodules.paths.event', 'Domain/Events');

        $this->runEasyModulesCommand('make-event', 'Shop', 'OrderCreated');

        // Check custom paths are used
        $this->assertFilenameExists('Shop/Domain/Events/OrderCreated.php');

        // Check custom namespaces
        $this->assertFileContains([
            'namespace Custom\\Modules\\Shop\\Domain\\Events;',
            'class OrderCreated',
        ], 'Shop/Domain/Events/OrderCreated.php');

        // Should NOT contain double namespace with custom config
        $this->assertFileNotContains([
            'namespace Custom\\Modules\\Shop\\Domain\\Events\\Domain\\Events;',
        ], 'Shop/Domain/Events/OrderCreated.php');
    }

    /**
     * Test event generation with different module structures
     */
    #[Test]
    public function it_handles_different_module_structures(): void
    {
        $testConfigs = [
            ['namespace' => 'App\\Components', 'eventPath' => 'Events'],
            ['namespace' => 'Modules', 'eventPath' => 'Domain/Events'],
            ['namespace' => 'Custom\\App\\Modules', 'eventPath' => 'Infrastructure/Events'],
        ];

        foreach ($testConfigs as $index => $config) {
            $this->app['config']->set('easymodules.base_namespace', $config['namespace']);
            $this->app['config']->set('easymodules.paths.event', $config['eventPath']);

            $eventName = "Test{$index}Event";

            $this->runEasyModulesCommand('make-event', 'Test', $eventName);

            $expectedEventPath = "Test/{$config['eventPath']}/{$eventName}.php";
            $this->assertFilenameExists($expectedEventPath);

            $expectedEventNamespace = str_replace('/', '\\', "{$config['namespace']}\\Test\\{$config['eventPath']}");
            $this->assertFileContains([
                "namespace {$expectedEventNamespace};",
                "class {$eventName}",
            ], $expectedEventPath);

            // Should not contain duplicated path segments
            $pathSegments = explode('/', $config['eventPath']);

            $duplicatedPatterns = [];
            foreach ($pathSegments as $segment) {
                $duplicatedPatterns[] = "{$expectedEventNamespace}\\{$segment}\\{$segment}";
            }

            $this->assertFileNotContains($duplicatedPatterns, $expectedEventPath);
        }
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // NIVEAU 1: EDGE CASE & INTEGRATION TESTS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Test event generation with complex names
     */
    #[Test]
    public function it_handles_complex_event_names(): void
    {
        $complexCases = [
            'UserRegistrationCompleted',
            'BlogPostPublished',
            'PaymentProcessed',
            'EmailVerificationSent',
        ];

        foreach ($complexCases as $eventName) {
            $this->runEasyModulesCommand('make-event', 'Test', $eventName);

            $this->assertFilenameExists("Test/Infrastructure/Events/{$eventName}.php");

            $this->assertFileContains([
                "class {$eventName}",
                'namespace App\\Modules\\Test\\Infrastructure\\Events;',
            ], "Test/Infrastructure/Events/{$eventName}.php");
        }
    }

    /**
     * Test event generation edge cases
     */
    #[Test]
    public function it_handles_edge_cases_gracefully(): void
    {
        // Test with single character names
        $this->runEasyModulesCommand('make-event', 'Blog', 'AEvent');

        $this->assertFilenameExists('Blog/Infrastructure/Events/AEvent.php');
        $this->assertFileContains([
            'class AEvent',
        ], 'Blog/Infrastructure/Events/AEvent.php');

        // Test with numbers in names
        $this->runEasyModulesCommand('make-event', 'Blog', 'Step2Completed');

        $this->assertFilenameExists('Blog/Infrastructure/Events/Step2Completed.php');
        $this->assertFileContains([
            'class Step2Completed',
        ], 'Blog/Infrastructure/Events/Step2Completed.php');
    }

    /**
     * Test event works with various module structures
     */
    #[Test]
    public function it_works_with_various_module_structures(): void
    {
        // Test multiple modules
        $modules = ['Blog', 'Shop', 'Admin'];

        foreach ($modules as $module) {
            $this->runEasyModulesCommand('make-event', $module, 'TestEvent');

            $this->assertFilenameExists("{$module}/Infrastructure/Events/TestEvent.php");

            $this->assertFileContains([
                "namespace App\\Modules\\{$module}\\Infrastructure\\Events;",
                'class TestEvent',
            ], "{$module}/Infrastructure/Events/TestEvent.php");
        }
    }

    /**
     * Test multiple events in same module
     */
    #[Test]
    public function it_handles_multiple_events_in_same_module(): void
    {
        $events = [
            'UserRegistered',
            'PostCreated',
            'CommentDeleted',
            'User/ProfileUpdated',
        ];

        foreach ($events as $eventPath) {
            $this->runEasyModulesCommand('make-event', 'Blog', $eventPath);

            $expectedFile = "Blog/Infrastructure/Events/{$eventPath}.php";
            $this->assertFilenameExists($expectedFile);
        }

        // Verify all files exist and have correct content
        foreach ($events as $eventPath) {
            $expectedFile = "Blog/Infrastructure/Events/{$eventPath}.php";
            $className    = basename($eventPath);
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
        $this->app['config']->set('easymodules.suffixes.event', 'Event');

        // Test suffix addition when missing
        $this->runEasyModulesCommand('make-event', 'Blog', 'UserRegistered');

        $this->assertFilenameExists('Blog/Infrastructure/Events/UserRegisteredEvent.php');
        $this->assertFileContains([
            'class UserRegisteredEvent',
        ], 'Blog/Infrastructure/Events/UserRegisteredEvent.php');

        // Test suffix not duplicated when already present
        $this->runEasyModulesCommand('make-event', 'Blog', 'PostCreatedEvent');

        $this->assertFilenameExists('Blog/Infrastructure/Events/PostCreatedEvent.php');
        $this->assertFileContains([
            'class PostCreatedEvent',
        ], 'Blog/Infrastructure/Events/PostCreatedEvent.php');

        // Should NOT create double suffix
        $this->assertFilenameNotExists('Blog/Infrastructure/Events/PostCreatedEventEvent.php');
    }

    /**
     * Test event naming conventions
     */
    #[Test]
    public function it_follows_event_naming_conventions(): void
    {
        $eventCases = [
            'UserCreated',
            'PostUpdated',
            'CommentDeleted',
            'OrderProcessed',
            'PaymentCompleted',
        ];

        foreach ($eventCases as $eventName) {
            $this->runEasyModulesCommand('make-event', 'Blog', $eventName);

            $this->assertFilenameExists("Blog/Infrastructure/Events/{$eventName}.php");

            $this->assertFileContains([
                "class {$eventName}",
                'namespace App\\Modules\\Blog\\Infrastructure\\Events;',
            ], "Blog/Infrastructure/Events/{$eventName}.php");
        }
    }

    /**
     * Test event with nested structure follows conventions
     */
    #[Test]
    public function it_handles_nested_event_conventions(): void
    {
        $nestedEvents = [
            'User/Registered',
            'Order/Placed',
            'Payment/Processed',
            'Email/Sent',
        ];

        foreach ($nestedEvents as $eventPath) {
            $this->runEasyModulesCommand('make-event', 'Blog', $eventPath);

            $this->assertFilenameExists("Blog/Infrastructure/Events/{$eventPath}.php");

            $className = basename($eventPath);
            $namespace = str_replace('/', '\\', dirname($eventPath));

            $this->assertFileContains([
                "namespace App\\Modules\\Blog\\Infrastructure\\Events\\{$namespace};",
                "class {$className}",
            ], "Blog/Infrastructure/Events/{$eventPath}.php");
        }
    }
}
