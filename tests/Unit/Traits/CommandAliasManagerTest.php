<?php

declare(strict_types=1);

namespace Kadevland\EasyModules\Tests\Unit\Traits;

use Illuminate\Console\Command;
use Kadevland\EasyModules\Tests\Support\TestCase;
use Kadevland\EasyModules\Traits\CommandAliasManager;
use PHPUnit\Framework\Attributes\Test;

class CommandAliasManagerTest extends TestCase
{
    protected $traitInstance;

    protected function setUp(): void
    {
        parent::setUp();

        $this->traitInstance = new class () extends Command
        {
            use CommandAliasManager;

            protected $name = 'test:command';
            protected $description = 'Test command';

            // Simulate parent configure method
            public function configure(): void
            {
                // Simulate parent configuration
            }

            public function getName(): string
            {
                return 'TestMakeCommand';
            }
        };
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // BASIC ALIAS CONFIGURATION TESTS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    #[Test]
    public function it_configures_with_aliases(): void
    {
        $aliases = ['test:alias1', 'test:alias2', 'alias3'];

        $reflection = new \ReflectionClass($this->traitInstance);
        $method = $reflection->getMethod('configureWithAliases');
        $method->setAccessible(true);

        $method->invoke($this->traitInstance, $aliases);

        $this->assertEquals($aliases, $this->traitInstance->getAliases());
    }

    #[Test]
    public function it_configures_with_empty_aliases(): void
    {
        $reflection = new \ReflectionClass($this->traitInstance);
        $method = $reflection->getMethod('configureWithAliases');
        $method->setAccessible(true);

        $method->invoke($this->traitInstance, []);

        $this->assertEquals([], $this->traitInstance->getAliases());
    }

    #[Test]
    public function it_configures_with_null_aliases(): void
    {
        $reflection = new \ReflectionClass($this->traitInstance);
        $method = $reflection->getMethod('configureWithAliases');
        $method->setAccessible(true);

        // Test with null aliases (should not set any aliases)
        $method->invoke($this->traitInstance);

        $this->assertEquals([], $this->traitInstance->getAliases());
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // EASYMODULES ALIAS GENERATION TESTS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    #[Test]
    public function it_generates_easymodules_aliases(): void
    {
        $reflection = new \ReflectionClass($this->traitInstance);
        $method = $reflection->getMethod('configureEasyModulesAliases');
        $method->setAccessible(true);

        $method->invoke($this->traitInstance, 'make-controller');

        $aliases = $this->traitInstance->getAliases();

        // Should contain standard prefix variations
        $this->assertContains('emodules:make-controller', $aliases);
        $this->assertContains('emodule:make-controller', $aliases);

        // Should contain alternative name variations
        $this->assertContains('emodules:controller', $aliases);
        $this->assertContains('emodule:controller', $aliases);
    }

    #[Test]
    public function it_generates_aliases_for_list_command(): void
    {
        $reflection = new \ReflectionClass($this->traitInstance);
        $method = $reflection->getMethod('configureEasyModulesAliases');
        $method->setAccessible(true);

        $method->invoke($this->traitInstance, 'list');

        $aliases = $this->traitInstance->getAliases();

        // Should contain standard prefix variations
        $this->assertContains('emodules:list', $aliases);
        $this->assertContains('emodule:list', $aliases);

        // Should contain alternative name (modules)
        $this->assertContains('emodules:modules', $aliases);
        $this->assertContains('emodule:modules', $aliases);
    }

    #[Test]
    public function it_handles_non_make_commands(): void
    {
        $reflection = new \ReflectionClass($this->traitInstance);
        $method = $reflection->getMethod('configureEasyModulesAliases');
        $method->setAccessible(true);

        $method->invoke($this->traitInstance, 'publish');

        $aliases = $this->traitInstance->getAliases();

        // Should contain standard prefix variations
        $this->assertContains('emodules:publish', $aliases);
        $this->assertContains('emodule:publish', $aliases);

        // Should not contain alternative names for non-make/non-list commands
        $this->assertNotContains('emodules:pub', $aliases);
        $this->assertNotContains('emodule:pub', $aliases);
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // ALIAS CUSTOMIZATION TESTS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    #[Test]
    public function it_includes_additional_aliases(): void
    {
        $additionalAliases = ['custom:alias1', 'custom:alias2'];

        $reflection = new \ReflectionClass($this->traitInstance);
        $method = $reflection->getMethod('configureEasyModulesAliases');
        $method->setAccessible(true);

        $method->invoke($this->traitInstance, 'make-model', $additionalAliases);

        $aliases = $this->traitInstance->getAliases();

        // Should contain additional aliases
        $this->assertContains('custom:alias1', $aliases);
        $this->assertContains('custom:alias2', $aliases);

        // Should also contain standard aliases
        $this->assertContains('emodules:make-model', $aliases);
        $this->assertContains('emodule:model', $aliases);
    }

    #[Test]
    public function it_gets_alternative_command_names(): void
    {
        $reflection = new \ReflectionClass($this->traitInstance);
        $method = $reflection->getMethod('getAlternativeCommandNames');
        $method->setAccessible(true);

        // Test make command
        $result = $method->invoke($this->traitInstance, 'make-controller');
        $this->assertEquals(['controller'], $result);

        $result = $method->invoke($this->traitInstance, 'make-model');
        $this->assertEquals(['model'], $result);

        // Test list command
        $result = $method->invoke($this->traitInstance, 'list');
        $this->assertEquals(['modules'], $result);

        // Test non-existent command
        $result = $method->invoke($this->traitInstance, 'non-existent');
        $this->assertEquals([], $result);
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // EDGE CASES AND INTEGRATION TESTS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    #[Test]
    public function it_handles_minimal_command_name(): void
    {
        $reflection = new \ReflectionClass($this->traitInstance);
        $method = $reflection->getMethod('configureEasyModulesAliases');
        $method->setAccessible(true);

        $method->invoke($this->traitInstance, 'test');

        $aliases = $this->traitInstance->getAliases();

        // Should contain prefix variations for minimal command
        $this->assertContains('emodules:test', $aliases);
        $this->assertContains('emodule:test', $aliases);

        // Should not contain alternative names for unknown commands
        $this->assertNotContains('emodules:t', $aliases);
        $this->assertNotContains('emodule:t', $aliases);
    }

    #[Test]
    public function it_configure_with_aliases_calls_parent_configure(): void
    {
        $reflection = new \ReflectionClass($this->traitInstance);
        $method = $reflection->getMethod('configureWithAliases');
        $method->setAccessible(true);

        // Should not crash when called without aliases
        $method->invoke($this->traitInstance);

        // Should work fine and not set any aliases
        $this->assertEquals([], $this->traitInstance->getAliases());
    }
}
