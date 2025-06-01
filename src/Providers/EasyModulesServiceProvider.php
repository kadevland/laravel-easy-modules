<?php

declare(strict_types=1);

namespace Kadevland\EasyModules\Providers;

use Illuminate\Support\ServiceProvider;
use Kadevland\EasyModules\Commands\Console\PublishCommand;
use Kadevland\EasyModules\Commands\Console\ListModulesCommand;
use Kadevland\EasyModules\Commands\Make\Laravel\JobMakeCommand;
use Kadevland\EasyModules\Commands\Make\Laravel\CastMakeCommand;
use Kadevland\EasyModules\Commands\Make\Laravel\MailMakeCommand;
use Kadevland\EasyModules\Commands\Make\Laravel\RuleMakeCommand;
use Kadevland\EasyModules\Commands\Make\Laravel\TestMakeCommand;
use Kadevland\EasyModules\Commands\Make\Laravel\EventMakeCommand;
use Kadevland\EasyModules\Commands\Make\Laravel\ModelMakeCommand;
use Kadevland\EasyModules\Commands\Make\Laravel\ScopeMakeCommand;
use Kadevland\EasyModules\Commands\Make\Laravel\PolicyMakeCommand;
use Kadevland\EasyModules\Commands\Make\Laravel\SeederMakeCommand;
use Kadevland\EasyModules\Commands\Make\EasyModule\StubMakeCommand;
use Kadevland\EasyModules\Commands\Make\Laravel\ChannelMakeCommand;
use Kadevland\EasyModules\Commands\Make\Laravel\ConsoleMakeCommand;
use Kadevland\EasyModules\Commands\Make\Laravel\FactoryMakeCommand;
use Kadevland\EasyModules\Commands\Make\Laravel\MigrateMakeCommand;
use Kadevland\EasyModules\Commands\Make\Laravel\RequestMakeCommand;
use Kadevland\EasyModules\Commands\Make\Laravel\ListenerMakeCommand;
use Kadevland\EasyModules\Commands\Make\Laravel\ObserverMakeCommand;
use Kadevland\EasyModules\Commands\Make\Laravel\ProviderMakeCommand;
use Kadevland\EasyModules\Commands\Make\Laravel\ResourceMakeCommand;
use Kadevland\EasyModules\Commands\Make\EasyModule\EntityMakeCommand;
use Kadevland\EasyModules\Commands\Make\EasyModule\ModuleMakeCommand;
use Kadevland\EasyModules\Commands\Make\Laravel\ComponentMakeCommand;
use Kadevland\EasyModules\Commands\Make\Laravel\InterfaceMakeCommand;
use Kadevland\EasyModules\Commands\Make\EasyModule\ServiceMakeCommand;
use Kadevland\EasyModules\Commands\Make\Laravel\ControllerMakeCommand;
use Kadevland\EasyModules\Commands\Make\Laravel\MiddlewareMakeCommand;
use Kadevland\EasyModules\Commands\Make\Laravel\NotificationMakeCommand;


/**
 * EasyModules service provider.
 *
 * Registers the package commands, publishes configuration and stubs,
 * and handles the package configuration.
 *
 * @package Kadevland\EasyModules\Providers
 *
 */
class EasyModulesServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->publishResources();
        $this->registerCommands();
    }

    /**
     * Register the application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom($this->configPath(), 'easymodules');
    }

    /**
     * Publish package resources.
     */
    protected function publishResources(): void
    {
        $stubsPath = __DIR__.'/../stubs';

        $this->publishes([
            $this->configPath() => config_path('easymodules.php'),
        ], 'config');

        $this->publishes([
            $stubsPath => resource_path('stubs/'),
        ], 'stubs');
    }

    /**
     * Register package commands.
     */
    protected function registerCommands(): void
    {
        $this->commands([
                // Core commands
            PublishCommand::class,
            ModuleMakeCommand::class,
            ListModulesCommand::class,

             // ─── Make Commands (alphabetical order) ───
            CastMakeCommand::class,
            ChannelMakeCommand::class,
            ConsoleMakeCommand::class,
            ComponentMakeCommand::class,
            ControllerMakeCommand::class,
            EntityMakeCommand::class,
            EventMakeCommand::class,
            FactoryMakeCommand::class,
            InterfaceMakeCommand::class,
            JobMakeCommand::class,
            ListenerMakeCommand::class,
            MailMakeCommand::class,
            MiddlewareMakeCommand::class,
            MigrateMakeCommand::class,
            ModelMakeCommand::class,
            NotificationMakeCommand::class,
            ObserverMakeCommand::class,
            PolicyMakeCommand::class,
            ProviderMakeCommand::class,
            RequestMakeCommand::class,
            ResourceMakeCommand::class,
            RuleMakeCommand::class,
            ScopeMakeCommand::class,
            SeederMakeCommand::class,
            StubMakeCommand::class,
            TestMakeCommand::class,

        ]);
    }

    /**
     * Get the configuration file path.
     *
     * @return string
     */
    protected function configPath(): string
    {
        return __DIR__.'/../../config/config.php';
    }
}
