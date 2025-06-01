<?php

declare(strict_types=1);

namespace Kadevland\EasyModules\Tests\Unit\Commands\Make\Laravel;

use Kadevland\EasyModules\Tests\Support\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Test suite for ListenerMakeCommand
 *
 * This command extends Laravel's base ListenerMakeCommand to generate
 * event listeners within the modular structure, supporting all Laravel
 * options like --event and --queued with intelligent event resolution.
 */
class ListenerMakeCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->setComponentPath('listener', 'Infrastructure/Listeners');
        $this->setComponentPath('event', 'Infrastructure/Events');

        // Set app environment to testing to disable prompts
        $this->app['config']->set('app.env', 'testing');
    }

    protected function tearDown(): void
    {
        // Enhanced cleanup - remove all test files
        $testPaths = [
            'Blog/Infrastructure/Listeners',
            'Blog/Infrastructure/Events',
            'Shop/Infrastructure/Listeners',
            'Shop/Domain/Events',
            'Test/Infrastructure/Listeners',
            'Test/Infrastructure/Events',
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
    // NIVEAU 5: BASIC LISTENER GENERATION TESTS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Test basic listener file generation
     */
    #[Test]
    public function it_can_generate_basic_listener_file(): void
    {
        $this->runEasyModulesCommand('make-listener', 'Blog', 'PostCreatedListener');

        $this->assertModuleComponentExists('Blog', 'Infrastructure/Listeners', 'PostCreatedListener', [
            'class PostCreatedListener',
            'public function handle(object $event)',
        ]);

        $this->assertFileNotContains([
            'class PostCreatedListener implements ShouldQueue',
        ], 'Blog/Infrastructure/Listeners/PostCreatedListener.php');
    }

    /**
     * Test listener generation without explicit event
     */
    #[Test]
    public function it_can_generate_listener_without_event(): void
    {
        $this->runEasyModulesCommand('make-listener', 'Blog', 'GeneralListener');

        $this->assertFilenameExists('Blog/Infrastructure/Listeners/GeneralListener.php');
        $this->assertFileContains([
            'namespace App\\Modules\\Blog\\Infrastructure\\Listeners;',
            'class GeneralListener',
            'public function handle(object $event)',
        ], 'Blog/Infrastructure/Listeners/GeneralListener.php');
    }

    /**
     * Test queued listener generation
     */
    #[Test]
    public function it_can_generate_queued_listener_file(): void
    {
        $this->runEasyModulesCommand('make-listener', 'Blog', 'QueuedListener', ['--queued' => true]);

        $this->assertModuleComponentExists('Blog', 'Infrastructure/Listeners', 'QueuedListener', [
            'use Illuminate\Contracts\Queue\ShouldQueue;',
            'class QueuedListener implements ShouldQueue',
            'public function handle(object $event)',
        ]);
    }

    /**
     * Test listener generation with explicit event option
     */
    #[Test]
    public function it_can_generate_listener_with_event_option(): void
    {
        $this->runEasyModulesCommand('make-listener', 'Blog', 'PostCreatedListener', ['--event' => 'PostCreated']);

        $this->assertModuleComponentExists('Blog', 'Infrastructure/Listeners', 'PostCreatedListener', [
            'use App\\Modules\\Blog\\Infrastructure\\Events\\PostCreated;',
            'class PostCreatedListener',
            'public function handle(PostCreated $event)',
        ]);

        // Check the namespace and event reference are correct
        $this->assertFileContains([
            'namespace App\\Modules\\Blog\\Infrastructure\\Listeners;',
        ], 'Blog/Infrastructure/Listeners/PostCreatedListener.php');

        // Check that double namespace is NOT present in use statement
        $this->assertFileNotContains([
            'use App\\Modules\\Blog\\Infrastructure\\Events\\App\\Modules\\Blog\\Infrastructure\\Events\\PostCreated;',
        ], 'Blog/Infrastructure/Listeners/PostCreatedListener.php');
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // NIVEAU 4: NAMESPACE CORRECTNESS TESTS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Test listener generation with nested event names
     */
    #[Test]
    public function it_can_generate_listener_for_nested_events(): void
    {
        $this->runEasyModulesCommand('make-listener', 'Blog', 'Content/ArticlePublishedListener', ['--event' => 'Content/ArticlePublished']);

        // Should create nested directory structure
        $this->assertFilenameExists('Blog/Infrastructure/Listeners/Content/ArticlePublishedListener.php');

        $this->assertFileContains([
            'namespace App\\Modules\\Blog\\Infrastructure\\Listeners\\Content;',
            'class ArticlePublishedListener',
            'ArticlePublished $event',
        ], 'Blog/Infrastructure/Listeners/Content/ArticlePublishedListener.php');
    }

    /**
     * Test listener with global event reference
     */
    #[Test]
    public function it_handles_global_event_references(): void
    {
        $this->runEasyModulesCommand('make-listener', 'Blog', 'GlobalEventListener', ['--event' => '\\App\\Events\\GlobalEvent']);

        $this->assertFilenameExists('Blog/Infrastructure/Listeners/GlobalEventListener.php');

        $this->assertFileContains([
            'App\\Events\\GlobalEvent',
        ], 'Blog/Infrastructure/Listeners/GlobalEventListener.php');

        // Should NOT try to modularize the global event
        $this->assertFileNotContains([
            'App\\Modules\\Blog\\Infrastructure\\Events\\App\\Events\\GlobalEvent',
        ], 'Blog/Infrastructure/Listeners/GlobalEventListener.php');
    }

    /**
     * Test listener namespace generation is correct
     */
    #[Test]
    public function it_generates_correct_listener_namespace(): void
    {
        $this->runEasyModulesCommand('make-listener', 'Blog', 'CommentListener', ['--event' => 'CommentCreated']);

        // Listener should have correct namespace
        $this->assertFileContains([
            'namespace App\\Modules\\Blog\\Infrastructure\\Listeners;',
        ], 'Blog/Infrastructure/Listeners/CommentListener.php');

        // Should NOT contain duplicated namespace segments
        $this->assertFileNotContains([
            'namespace App\\Modules\\Blog\\Infrastructure\\Listeners\\Infrastructure\\Listeners;',
            'namespace App\\Modules\\Blog\\Blog\\Infrastructure\\Listeners;',
        ], 'Blog/Infrastructure/Listeners/CommentListener.php');
    }

    /**
     * Test event reference in listener is correct
     */
    #[Test]
    public function it_generates_correct_event_references(): void
    {
        $this->runEasyModulesCommand('make-listener', 'Blog', 'TagListener', ['--event' => 'TagCreated']);

        // Should contain correct event reference in use statement
        $this->assertFileContains([
            'use App\\Modules\\Blog\\Infrastructure\\Events\\TagCreated;',
        ], 'Blog/Infrastructure/Listeners/TagListener.php');

        // Should NOT contain double namespace in any event references
        $this->assertFileNotContains([
            'App\\Modules\\Blog\\Infrastructure\\Events\\App\\Modules\\Blog\\Infrastructure\\Events\\TagCreated',
        ], 'Blog/Infrastructure/Listeners/TagListener.php');
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // NIVEAU 3: DOUBLE PATH PREVENTION TESTS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Test prevention of double path and namespace duplication
     *
     * This test ensures that neither file paths nor namespaces are duplicated
     * when generating listeners within the module structure.
     */
    #[Test]
    public function it_prevents_double_path_and_namespace_duplication(): void
    {
        $this->runEasyModulesCommand('make-listener', 'Blog', 'UserListener', ['--event' => 'UserCreated']);

        // ✅ File path verification
        $this->assertFilenameExists('Blog/Infrastructure/Listeners/UserListener.php');
        $this->assertFilenameNotExists('Blog/Infrastructure/Listeners/Infrastructure/Listeners/UserListener.php');

        // ✅ Namespace duplication prevention
        $this->assertFileNotContains([
            'App\\Modules\\Blog\\Infrastructure\\Listeners\\Infrastructure\\Listeners',
            'App\\Modules\\Blog\\Blog\\Infrastructure\\Listeners',
            'Infrastructure\\Listeners\\Infrastructure\\Listeners',
            'App\\Modules\\Blog\\Infrastructure\\Events\\App\\Modules\\Blog\\Infrastructure\\Events',
        ], 'Blog/Infrastructure/Listeners/UserListener.php');
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // NIVEAU 2: CUSTOM CONFIGURATION TESTS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Test queued listener with event
     */
    #[Test]
    public function it_can_generate_queued_listener_with_event(): void
    {
        $this->runEasyModulesCommand('make-listener', 'Blog', 'QueuedPostListener', [
            '--event'  => 'PostCreated',
            '--queued' => true,
        ]);

        $this->assertModuleComponentExists('Blog', 'Infrastructure/Listeners', 'QueuedPostListener', [
            'use App\\Modules\\Blog\\Infrastructure\\Events\\PostCreated;',
            'use Illuminate\\Contracts\\Queue\\ShouldQueue;',
            'class QueuedPostListener implements ShouldQueue',
            'public function handle(PostCreated $event)',
        ]);
    }

    /**
     * Test queued listener imports and structure
     */
    #[Test]
    public function it_generates_correct_queued_listener_structure(): void
    {
        $this->runEasyModulesCommand('make-listener', 'Blog', 'QueueStructureListener', ['--queued' => true]);

        $this->assertFileContains([
            'use Illuminate\\Contracts\\Queue\\ShouldQueue;',
            'class QueueStructureListener implements ShouldQueue',
        ], 'Blog/Infrastructure/Listeners/QueueStructureListener.php');
    }

    /**
     * Test listener generation with custom module configuration
     */
    #[Test]
    public function it_handles_custom_module_configurations(): void
    {
        // Test with custom base namespace and paths
        $this->app['config']->set('easymodules.base_namespace', 'Custom\\Modules');
        $this->app['config']->set('easymodules.paths.listener', 'Domain/Listeners');
        $this->app['config']->set('easymodules.paths.event', 'Domain/Events');

        $this->runEasyModulesCommand('make-listener', 'Shop', 'ProductListener', ['--event' => 'ProductCreated']);

        // Check custom paths are used
        $this->assertFilenameExists('Shop/Domain/Listeners/ProductListener.php');

        // Check custom namespaces
        $this->assertFileContains([
            'namespace Custom\\Modules\\Shop\\Domain\\Listeners;',
            'class ProductListener',
        ], 'Shop/Domain/Listeners/ProductListener.php');

        // Should reference custom event namespace
        $this->assertFileContains([
            'Custom\\Modules\\Shop\\Domain\\Events\\ProductCreated',
        ], 'Shop/Domain/Listeners/ProductListener.php');

        // Should NOT contain double namespace with custom config
        $this->assertFileNotContains([
            'Custom\\Modules\\Shop\\Domain\\Events\\Custom\\Modules\\Shop\\Domain\\Events\\ProductCreated',
        ], 'Shop/Domain/Listeners/ProductListener.php');
    }

    /**
     * Test listener generation with different module structures
     */
    #[Test]
    public function it_handles_different_module_structures(): void
    {
        $testConfigs = [
            ['namespace' => 'App\\Components', 'listenerPath' => 'Listeners', 'eventPath' => 'Events'],
            ['namespace' => 'Modules', 'listenerPath' => 'Event/Listeners', 'eventPath' => 'Event/Events'],
        ];

        foreach ($testConfigs as $index => $config) {
            $this->app['config']->set('easymodules.base_namespace', $config['namespace']);
            $this->app['config']->set('easymodules.paths.listener', $config['listenerPath']);
            $this->app['config']->set('easymodules.paths.event', $config['eventPath']);

            $listenerName = "Test{$index}Listener";
            $eventName    = "Test{$index}Event";

            $this->runEasyModulesCommand('make-listener', 'Test', $listenerName, ['--event' => $eventName]);

            $expectedListenerPath = "Test/{$config['listenerPath']}/{$listenerName}.php";
            $this->assertFilenameExists($expectedListenerPath);

            $expectedListenerNamespace = str_replace('/', '\\', "{$config['namespace']}\\Test\\{$config['listenerPath']}");
            $expectedEventNamespace    = str_replace('/', '\\', "{$config['namespace']}\\Test\\{$config['eventPath']}\\{$eventName}");

            $this->assertFileContains([
                "namespace {$expectedListenerNamespace};",
                "class {$listenerName}",
                $expectedEventNamespace,
            ], $expectedListenerPath);

            // Should not contain duplicated path segments
            $pathSegments = explode('/', $config['listenerPath']);

            $duplicatedPatterns = [];
            foreach ($pathSegments as $segment) {
                $duplicatedPatterns[] = "{$expectedListenerNamespace}\\{$segment}\\{$segment}";
            }

            $this->assertFileNotContains($duplicatedPatterns, $expectedListenerPath);
        }
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // NIVEAU 1: EDGE CASE & INTEGRATION TESTS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Test event resolution with different event naming patterns
     */
    #[Test]
    public function it_handles_different_event_naming_patterns(): void
    {
        $eventCases = [
            ['listener' => 'UserRegisteredListener', 'event' => 'UserRegistered'],
            ['listener' => 'OrderProcessedListener', 'event' => 'OrderProcessed'],
            ['listener' => 'EmailSentListener', 'event' => 'EmailSent'],
            ['listener' => 'PaymentFailedListener', 'event' => 'PaymentFailed'],
        ];

        foreach ($eventCases as $case) {
            $this->runEasyModulesCommand('make-listener', 'Test', $case['listener'], ['--event' => $case['event']]);

            $this->assertFilenameExists("Test/Infrastructure/Listeners/{$case['listener']}.php");

            $this->assertFileContains([
                "App\\Modules\\Test\\Infrastructure\\Events\\{$case['event']}",
                "public function handle({$case['event']} \$event)",
            ], "Test/Infrastructure/Listeners/{$case['listener']}.php");
        }
    }

    /**
     * Test listener handles Laravel core events
     */
    #[Test]
    public function it_handles_laravel_core_events(): void
    {
        $coreEvents = [
            'Illuminate\\Auth\\Events\\Login',
            'Illuminate\\Auth\\Events\\Logout',
            'Illuminate\\Queue\\Events\\JobFailed',
        ];

        foreach ($coreEvents as $index => $coreEvent) {
            $listenerName = "CoreEvent{$index}Listener";

            $this->runEasyModulesCommand('make-listener', 'Blog', $listenerName, ['--event' => $coreEvent]);

            $this->assertFilenameExists("Blog/Infrastructure/Listeners/{$listenerName}.php");

            $this->assertFileContains([
                $coreEvent,
            ], "Blog/Infrastructure/Listeners/{$listenerName}.php");

            // Should not try to modularize core Laravel events
            $this->assertFileNotContains([
                "App\\Modules\\Blog\\Infrastructure\\Events\\{$coreEvent}",
            ], "Blog/Infrastructure/Listeners/{$listenerName}.php");
        }
    }

    /**
     * Test that generated listeners have proper method structure
     */
    #[Test]
    public function it_generates_proper_listener_method_structure(): void
    {
        $this->runEasyModulesCommand('make-listener', 'Blog', 'MethodStructureListener', ['--event' => 'TestEvent']);

        $this->assertFileContains([
            'public function handle(TestEvent $event)',
            'public function handle(TestEvent $event): void',
        ], 'Blog/Infrastructure/Listeners/MethodStructureListener.php');
    }

    /**
     * Test listener without event has generic handle method
     */
    #[Test]
    public function it_generates_generic_handle_method_without_event(): void
    {
        $this->runEasyModulesCommand('make-listener', 'Blog', 'GenericListener');

        $this->assertFileContains([
            'public function handle(object $event)',
            'public function handle(object $event): void',
        ], 'Blog/Infrastructure/Listeners/GenericListener.php');
    }

    /**
     * Test listener generation edge cases
     */
    #[Test]
    public function it_handles_edge_cases_gracefully(): void
    {
        // Test with single character names
        $this->runEasyModulesCommand('make-listener', 'Blog', 'AListener', ['--event' => 'A']);

        $this->assertFilenameExists('Blog/Infrastructure/Listeners/AListener.php');
        $this->assertFileContains([
            'App\\Modules\\Blog\\Infrastructure\\Events\\A',
            'A $event',
        ], 'Blog/Infrastructure/Listeners/AListener.php');
    }

    /**
     * Test listener works with various module structures
     */
    #[Test]
    public function it_works_with_various_module_structures(): void
    {
        // Test multiple modules
        $modules = ['Blog', 'Shop', 'Admin'];

        foreach ($modules as $module) {
            $this->runEasyModulesCommand('make-listener', $module, 'TestListener', ['--event' => 'TestEvent']);

            $this->assertFilenameExists("{$module}/Infrastructure/Listeners/TestListener.php");

            $this->assertFileContains([
                "namespace App\\Modules\\{$module}\\Infrastructure\\Listeners;",
                'class TestListener',
                "App\\Modules\\{$module}\\Infrastructure\\Events\\TestEvent",
            ], "{$module}/Infrastructure/Listeners/TestListener.php");
        }
    }

    /**
     * Test multiple listeners in same module
     */
    #[Test]
    public function it_handles_multiple_listeners_in_same_module(): void
    {
        $listeners = [
            ['name' => 'PostCreatedListener', 'event' => 'PostCreated'],
            ['name' => 'PostUpdatedListener', 'event' => 'PostUpdated'],
            ['name' => 'PostDeletedListener', 'event' => 'PostDeleted'],
            ['name' => 'UserRegisteredListener', 'event' => 'UserRegistered'],
        ];

        foreach ($listeners as $listener) {
            $this->runEasyModulesCommand('make-listener', 'Blog', $listener['name'], ['--event' => $listener['event']]);

            $this->assertFilenameExists("Blog/Infrastructure/Listeners/{$listener['name']}.php");
        }

        // Verify all files exist and have correct content
        foreach ($listeners as $listener) {
            $this->assertFileContains([
                "class {$listener['name']}",
                "App\\Modules\\Blog\\Infrastructure\\Events\\{$listener['event']}",
            ], "Blog/Infrastructure/Listeners/{$listener['name']}.php");
        }
    }

    /**
     * Test listener with include-global option for event suggestions
     */
    #[Test]
    public function it_supports_include_global_option(): void
    {
        // This tests that the command accepts the --include-global option
        // The actual event suggestion functionality would be tested in integration tests
        $this->runEasyModulesCommand('make-listener', 'Blog', 'GlobalListener', [
            '--event'          => 'PostCreated',
            '--include-global' => true,
        ]);

        $this->assertFilenameExists('Blog/Infrastructure/Listeners/GlobalListener.php');

        // Should still generate correct modular listener
        $this->assertFileContains([
            'App\\Modules\\Blog\\Infrastructure\\Events\\PostCreated',
        ], 'Blog/Infrastructure/Listeners/GlobalListener.php');
    }

    /**
     * Test combination of queued and event options
     */
    #[Test]
    public function it_handles_queued_and_event_combination(): void
    {
        $this->runEasyModulesCommand('make-listener', 'Blog', 'QueuedEventListener', [
            '--event'  => 'ImportantEvent',
            '--queued' => true,
        ]);

        $this->assertFileContains([
            'use App\\Modules\\Blog\\Infrastructure\\Events\\ImportantEvent;',
            'use Illuminate\\Contracts\\Queue\\ShouldQueue;',
            'class QueuedEventListener implements ShouldQueue',
            'public function handle(ImportantEvent $event)',
        ], 'Blog/Infrastructure/Listeners/QueuedEventListener.php');
    }

    /**
     * Test suffix configuration behavior
     */
    #[Test]
    public function it_handles_suffix_configuration(): void
    {
        // Enable suffix appending
        $this->app['config']->set('easymodules.append_suffix', true);
        $this->app['config']->set('easymodules.suffixes.listener', 'Listener');

        // Test suffix addition when missing
        $this->runEasyModulesCommand('make-listener', 'Blog', 'UserHandler', ['--event' => 'UserCreated']);

        $this->assertFilenameExists('Blog/Infrastructure/Listeners/UserHandlerListener.php');
        $this->assertFileContains([
            'class UserHandlerListener',
        ], 'Blog/Infrastructure/Listeners/UserHandlerListener.php');

        // Test suffix not duplicated when already present
        $this->runEasyModulesCommand('make-listener', 'Blog', 'PostListener', ['--event' => 'PostCreated']);

        $this->assertFilenameExists('Blog/Infrastructure/Listeners/PostListener.php');
        $this->assertFileContains([
            'class PostListener',
        ], 'Blog/Infrastructure/Listeners/PostListener.php');

        // Should NOT create double suffix
        $this->assertFilenameNotExists('Blog/Infrastructure/Listeners/PostListenerListener.php');
    }
}
