<?php

declare(strict_types=1);

namespace Kadevland\EasyModules\Commands\Make\EasyModule;

/**
 * Command to create domain entity classes within modules
 *
 * This command extends ModuleGeneratorCommand to generate
 * domain entity classes within the modular structure following
 * Clean Architecture principles.
 *
 * @package Kadevland\EasyModules\Commands\Make\EasyModule
 * @author  Kadevland <kadevland@kaosland.net>
 * @version 1.0.0
 */
class EntityMakeCommand extends ModuleGeneratorCommand
{
    // ═══════════════════════════════════════════════════════════════════════════════════════
    // PROPERTIES
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * The component type for this command
     *
     * @var string
     */
    protected string $componentType = 'entity';

    /**
     * The console command name
     *
     * @var string
     */
    protected $name = 'easymodules:make-entity';

    /**
     * The console command description
     *
     * @var string
     */
    protected $description = 'Create a new domain entity class within a module';

    /**
     * The type of class being generated
     *
     * @var string
     */
    protected $type = 'Entity';

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // CONFIGURATION
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Configure the command options and aliases.
     *
     * @return void
     */
    protected function configure(): void
    {
        $commandName = $this->getCommandBaseName();
        $this->configureModuleAliases($commandName);
        parent::configure();
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // STUB REPLACEMENT METHODS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Build replacement variables specific to entities
     *
     * @return array Array of placeholder replacements
     */
    protected function buildReplacements(): array
    {
        $baseReplacements = parent::buildReplacements();
        $entityName       = class_basename($this->getNameInput());

        return array_merge($baseReplacements, [
            '{{ entity_id_type }}'  => 'string|int',
            '{{ entity_variable }}' => \Illuminate\Support\Str::camel($entityName),
        ]);
    }
}
