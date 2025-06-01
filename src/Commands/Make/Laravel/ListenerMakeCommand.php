<?php

declare(strict_types=1);

namespace Kadevland\EasyModules\Commands\Make\Laravel;

use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputOption;
use Kadevland\EasyModules\Traits\HandlesModuleMakeCommands;
use Illuminate\Foundation\Console\ListenerMakeCommand as BaseListenerMakeCommand;

/**
 * Command to create listener classes within modules
 *
 * This command extends Laravel's base ListenerMakeCommand to generate
 * event listeners within the modular structure, supporting all Laravel
 * options like --event and --queued with intelligent event resolution.
 *
 * @package Kadevland\EasyModules\Commands\Make\Laravel
 * @author  Kadevland <kadevland@kaosland.net>
 * @version 1.0.0
 */
class ListenerMakeCommand extends BaseListenerMakeCommand
{
    use HandlesModuleMakeCommands;

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // PROPERTIES
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * The component type for this command
     *
     * @var string
     */
    protected string $componentType = 'listener';

    /**
     * The console command name
     *
     * @var string
     */
    protected $name = 'easymodules:make-listener';

    /**
     * The console command description
     *
     * @var string
     */
    protected $description = 'Create a new event listener class within a module';

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // CONFIGURATION
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Configure the command options and aliases
     *
     * @return void
     */
    protected function configure(): void
    {
        $this->configureModuleAliases('make-listener');
        parent::configure();
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions(): array
    {
        return array_merge(parent::getOptions(), [
            ['include-global', 'ig', InputOption::VALUE_NONE, 'Include global event in suggestions'],
        ]);
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // CORE GENERATOR METHODS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Get the destination class path within the module
     *
     * @param string $name The fully qualified class name
     * @return string The file path where the class should be created
     */
    protected function getPath($name): string
    {
        $name = Str::replaceFirst($this->rootNamespace(), '', $name);
        return $this->rootModulePath().'/'.ltrim(str_replace('\\', '/', $name).'.php', '/');
    }

    /**
     * Get the default namespace for the class within the module
     *
     * @param string $rootNamespace The root namespace of the application
     * @return string The default namespace for this component type in the module
     */
    protected function getDefaultNamespace($rootNamespace): string
    {
        return $this->moduleNamespace($this->getComponentType(), 'Infrastructure\\Listeners');
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // EVENT INTEGRATION METHODS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Replace namespace and handle event resolution in module context
     *
     * This method processes custom replacements before Laravel's default replacements,
     * following the correct pattern for stub processing.
     *
     * @param string $stub The stub content
     * @param string $name The class name
     * @return mixed
     */
    protected function replaceNamespace(&$stub, $name)
    {
        $event = $this->option('event') ?? '';

        if ($event && ! Str::startsWith($event, [
            $this->rootModuleNamespace(),
            $this->laravel->getNamespace(),
            'Illuminate',
            '\\',
        ])) {
            $moduleEventNamespace = $this->moduleNamespace('event', 'Infrastructure\\Events');
            $event                = $moduleEventNamespace.'\\'.str_replace('/', '\\', $event);
        }

        if ($event) {
            $searches = [
                'DummyEvent'           => class_basename($event),
                '{{ event }}'          => class_basename($event),
                'DummyFullEvent'       => trim($event, '\\'),
                '{{ eventNamespace }}' => trim($event, '\\'),
            ];

            $stub = str_replace(array_keys($searches), array_values($searches), $stub);
        }

        return parent::replaceNamespace($stub, $name);
    }

    /**
     * Get all possible event class names
     *
     * Override to include module events in suggestions.
     *
     * @return array Array of possible event names
     */
    protected function possibleEvents(): array
    {
        return $this->getPossibleComponents('event',
            $this->option('include-global') ? fn () => parent::possibleEvents() : null
        );
    }
}
