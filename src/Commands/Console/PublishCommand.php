<?php

declare(strict_types=1);

namespace Kadevland\EasyModules\Commands\Console;

use Illuminate\Console\Command;
use Kadevland\EasyModules\Traits\CommandAliasManager;

/**
 * Command to publish configuration files and stubs for Easy Module
 *
 * This command provides an easy way to publish the package's configuration
 * and stub files with various options for flexibility.
 *
 * @package Kadevland\EasyModules\Commands\Console
 * @author  Kadevland <kadevland@kaosland.net>
 * @version 1.0.0
 */
class PublishCommand extends Command
{
    use CommandAliasManager;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'easymodules:publish
                            {--all : Publish both config and stubs}
                            {--stubs : Publish stubs only}
                            {--force : Force overwrite existing files}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publish Easy Module configuration files and stubs';

    /**
     * Configure the command options and aliases.
     *
     * @return void
     */
    protected function configure(): void
    {
        $this->configureEasyModulesAliases('publish');
        parent::configure();
    }

    /**
     * Execute the console command.
     *
     * @return int Command exit code (0 for success, 1 for failure)
     */
    public function handle(): int
    {
        $publishConfig = $this->shouldPublishConfig();
        $publishStubs = $this->shouldPublishStubs();

        if (!$publishConfig && !$publishStubs) {
            $this->error('❌ No valid publish option specified.');
            $this->info('💡 Use --all, --stubs, or run without options to publish config only.');
            return Command::FAILURE;
        }

        $overallSuccess = true;

        if ($publishConfig) {
            $overallSuccess = $this->publishConfig() && $overallSuccess;
        }

        if ($publishStubs) {
            $overallSuccess = $this->publishStubs() && $overallSuccess;
        }

        if ($overallSuccess) {
            $this->info('✅ Easy Module files published successfully!');
        } else {
            $this->error('❌ Some files failed to publish. Check the output above.');
        }

        return $overallSuccess ? Command::SUCCESS : Command::FAILURE;
    }

    /**
     * Determine if configuration should be published.
     *
     * @return bool True if config should be published
     */
    protected function shouldPublishConfig(): bool
    {
        return $this->option('all') || (!$this->option('stubs'));
    }

    /**
     * Determine if stubs should be published.
     *
     * @return bool True if stubs should be published
     */
    protected function shouldPublishStubs(): bool
    {
        return $this->option('all') || $this->option('stubs');
    }

    /**
     * Publish the configuration files.
     *
     * @return bool True if publishing succeeded, false otherwise
     */
    protected function publishConfig(): bool
    {
        $this->info('📄 Publishing configuration files...');

        $exitCode = $this->call('vendor:publish', [
            '--provider' => 'Kadevland\\EasyModules\\Providers\\EasyModulesServiceProvider',
            '--tag' => 'config',
            '--force' => $this->option('force'),
        ]);

        if ($exitCode === Command::SUCCESS) {
            $this->info('✅ Configuration files published successfully.');
            return true;
        }

        $this->error('❌ Failed to publish configuration files.');
        return false;
    }

    /**
     * Publish the stub files.
     *
     * @return bool True if publishing succeeded, false otherwise
     */
    protected function publishStubs(): bool
    {
        $this->info('📄 Publishing stub files...');

        $exitCode = $this->call('vendor:publish', [
            '--provider' => 'Kadevland\\EasyModules\\Providers\\EasyModulesServiceProvider',
            '--tag' => 'stubs',
            '--force' => $this->option('force'),
        ]);

        if ($exitCode === Command::SUCCESS) {
            $this->info('✅ Stub files published successfully.');
            return true;
        }

        $this->error('❌ Failed to publish stub files.');
        return false;
    }
}
