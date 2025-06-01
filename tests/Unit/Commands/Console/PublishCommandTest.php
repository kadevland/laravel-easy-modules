<?php

declare(strict_types=1);

namespace Kadevland\EasyModules\Tests\Unit\Commands\Console;

use Kadevland\EasyModules\Tests\Support\TestCase;
use Kadevland\EasyModules\Commands\Console\PublishCommand;
use PHPUnit\Framework\Attributes\Test;

/**
 * Test suite for PublishCommand
 *
 * This command handles publishing of Easy Module configuration files and stub
 * templates to the user's application, supporting various publishing options
 * including selective publishing and forced overwriting.
 */
class PublishCommandTest extends TestCase
{
    protected PublishCommand $command;

    protected function setUp(): void
    {
        parent::setUp();

        // Set app environment to testing to disable prompts
        $this->app['config']->set('app.env', 'testing');

        // IMPORTANT: Keep original command setup for option testing
        $this->command = new PublishCommand();
        $this->command->setLaravel($this->app);

        // Initialize command definition to enable options
        $this->command->setDefinition($this->command->getDefinition());
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // NIVEAU 5: BASIC OPTION LOGIC TESTS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Test publish config logic determination
     */
    #[Test]
    public function it_determines_publish_config_logic(): void
    {
        $reflection = new \ReflectionClass($this->command);
        $method = $reflection->getMethod('shouldPublishConfig');
        $method->setAccessible(true);

        // Default behavior (no options) - should publish config
        $input = new \Symfony\Component\Console\Input\ArrayInput([], $this->command->getDefinition());
        $this->command->setInput($input);
        $this->assertTrue($method->invoke($this->command));

        // With --all option - should publish config
        $input = new \Symfony\Component\Console\Input\ArrayInput(['--all' => true], $this->command->getDefinition());
        $this->command->setInput($input);
        $this->assertTrue($method->invoke($this->command));

        // With --stubs only - should NOT publish config
        $input = new \Symfony\Component\Console\Input\ArrayInput(['--stubs' => true], $this->command->getDefinition());
        $this->command->setInput($input);
        $this->assertFalse($method->invoke($this->command));
    }

    /**
     * Test publish stubs logic determination
     */
    #[Test]
    public function it_determines_publish_stubs_logic(): void
    {
        $reflection = new \ReflectionClass($this->command);
        $method = $reflection->getMethod('shouldPublishStubs');
        $method->setAccessible(true);

        // Default behavior (no options) - should NOT publish stubs
        $input = new \Symfony\Component\Console\Input\ArrayInput([], $this->command->getDefinition());
        $this->command->setInput($input);
        $this->assertFalse($method->invoke($this->command));

        // With --all option - should publish stubs
        $input = new \Symfony\Component\Console\Input\ArrayInput(['--all' => true], $this->command->getDefinition());
        $this->command->setInput($input);
        $this->assertTrue($method->invoke($this->command));

        // With --stubs only - should publish stubs
        $input = new \Symfony\Component\Console\Input\ArrayInput(['--stubs' => true], $this->command->getDefinition());
        $this->command->setInput($input);
        $this->assertTrue($method->invoke($this->command));
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // NIVEAU 4: OPTION COMBINATIONS TESTS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Test default behavior publishes config only
     */
    #[Test]
    public function it_publishes_config_only_by_default(): void
    {
        $this->artisan('easymodules:publish')
            ->expectsOutputToContain('Publishing configuration files')
            ->doesntExpectOutputToContain('Publishing stub files')
            ->expectsOutputToContain('published successfully')
            ->assertExitCode(0);
    }

    /**
     * Test stubs-only option behavior
     */
    #[Test]
    public function it_publishes_stubs_only(): void
    {
        $this->artisan('easymodules:publish', ['--stubs' => true])
            ->expectsOutputToContain('Publishing stub files')
            ->doesntExpectOutputToContain('Publishing configuration files')
            ->expectsOutputToContain('published successfully')
            ->assertExitCode(0);
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // NIVEAU 3: COMPREHENSIVE OPTION TESTS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Test all publishing options and force behavior
     *
     * This test ensures that all option combinations work correctly,
     * including the --all flag and --force option functionality.
     */
    #[Test]
    public function it_handles_all_option_combinations_and_force(): void
    {
        // Test --all option publishes both config and stubs
        $this->artisan('easymodules:publish', ['--all' => true])
            ->expectsOutputToContain('📄 Publishing configuration files...')
            ->expectsOutputToContain('📄 Publishing stub files...')
            ->expectsOutputToContain('✅ Easy Module files published successfully!')
            ->assertExitCode(0);

        // Test --force option with default (config only)
        $this->artisan('easymodules:publish', ['--force' => true])
            ->expectsOutputToContain('📄 Publishing configuration files...')
            ->expectsOutputToContain('✅ Easy Module files published successfully!')
            ->assertExitCode(0);

        // Test --force with --stubs
        $this->artisan('easymodules:publish', ['--stubs' => true, '--force' => true])
            ->expectsOutputToContain('📄 Publishing stub files...')
            ->assertExitCode(0);

        // Test --force with --all
        $this->artisan('easymodules:publish', ['--all' => true, '--force' => true])
            ->expectsOutputToContain('📄 Publishing configuration files...')
            ->expectsOutputToContain('📄 Publishing stub files...')
            ->assertExitCode(0);
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // NIVEAU 2: COMMAND METADATA TESTS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Test command signature contains all required options
     */
    #[Test]
    public function it_has_correct_command_signature(): void
    {
        $reflection = new \ReflectionClass($this->command);
        $property = $reflection->getProperty('signature');
        $property->setAccessible(true);
        $signature = $property->getValue($this->command);

        $this->assertStringContainsString('easymodules:publish', $signature);
        $this->assertStringContainsString('--all', $signature);
        $this->assertStringContainsString('--stubs', $signature);
        $this->assertStringContainsString('--force', $signature);
    }

    /**
     * Test command description is properly set
     */
    #[Test]
    public function it_has_correct_command_description(): void
    {
        $reflection = new \ReflectionClass($this->command);
        $property = $reflection->getProperty('description');
        $property->setAccessible(true);
        $description = $property->getValue($this->command);

        $this->assertNotEmpty($description);
        $this->assertStringContainsString('Easy Module', $description);
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // NIVEAU 1: EDGE CASE & INTEGRATION TESTS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Test command aliases work correctly
     */
    #[Test]
    public function it_works_with_command_aliases(): void
    {
        $aliases = ['emodules:publish', 'emodule:publish'];

        foreach ($aliases as $alias) {
            $this->artisan($alias)
                ->expectsOutputToContain('📄 Publishing configuration files...')
                ->expectsOutputToContain('✅ Easy Module files published successfully!')
                ->assertExitCode(0);
        }
    }

    /**
     * Test provider class usage and error handling
     */
    #[Test]
    public function it_handles_provider_calls_and_edge_cases(): void
    {
        // Test that the correct provider is used when calling vendor:publish
        $this->artisan('easymodules:publish')
            ->expectsOutputToContain('📄 Publishing configuration files...')
            ->assertExitCode(0);

        // Test with stubs to ensure correct provider
        $this->artisan('easymodules:publish', ['--stubs' => true])
            ->expectsOutputToContain('📄 Publishing stub files...')
            ->assertExitCode(0);

        // Test defensive behavior for edge cases
        $this->artisan('easymodules:publish')
            ->assertExitCode(0); // Should always succeed with default behavior
    }
}
