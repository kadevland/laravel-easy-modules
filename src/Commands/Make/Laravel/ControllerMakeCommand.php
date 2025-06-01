<?php

declare(strict_types=1);

namespace Kadevland\EasyModules\Commands\Make\Laravel;

use Illuminate\Support\Str;
use Kadevland\EasyModules\Traits\ParsesModuleModels;
use Kadevland\EasyModules\Traits\HandlesModuleMakeCommands;
use Illuminate\Routing\Console\ControllerMakeCommand as BaseControllerMakeCommand;

/**
 * Command to create controller classes within modules.
 *
 * This command extends Laravel's base ControllerMakeCommand to generate
 * controllers within the modular structure, supporting ALL Laravel options:
 * --api, --type, --force, --invokable, --model, --parent, --resource,
 * --requests, --singleton, --creatable, --test, --pest
 *
 * @package Kadevland\EasyModules\Commands\Make\Laravel
 * @author  Kadevland <kadevland@kaosland.net>
 * @version 1.0.0
 */
class ControllerMakeCommand extends BaseControllerMakeCommand
{
    use HandlesModuleMakeCommands, ParsesModuleModels;

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // PROPERTIES
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * The component type for this command.
     *
     * @var string
     */
    protected string $componentType = 'controller';

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'easymodules:make-controller';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new controller class within a module';

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
        $this->configureModuleAliases('make-controller');
        parent::configure();
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // CORE GENERATOR METHODS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Get the destination class path within the module.
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
     * Get the default namespace for the class within the module.
     *
     * @param string $rootNamespace The root namespace of the application
     * @return string The default namespace for this component type in the module
     */
    protected function getDefaultNamespace($rootNamespace): string
    {
        return $this->moduleNamespace($this->getComponentType(), 'Presentation\\Http\\Controllers');
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // FORM REQUEST GENERATION METHODS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Build the model replacement values.
     *
     * ⚠️ Overridden: Behavior differs from the parent implementation
     * due to the use of a hardcoded namespace, which prevents support
     * for a modular namespace structure.
     *
     * @param array $replace
     * @param string $modelClass
     * @return array
     */
    protected function buildFormRequestReplacements(array $replace, $modelClass)
    {
        // NOTE: Original base implementation hardcoded the namespace:
        // $namespace = 'App\\Http\\Requests';

        // We override this behavior in the child class using generateFormRequests()
        // to apply dynamic module-based namespacing. This makes the code flexible and
        // better aligned with a modular architecture.
        //
        // ⚠️ If future framework updates change how Form Requests are handled or generated,
        // revisit this override and ensure compatibility or refactor accordingly.

        [$namespace, $storeRequestClass, $updateRequestClass] = [
            'Illuminate\\Http',
            'Request',
            'Request',
        ];

        if ($this->option('requests')) {
            [$storeRequestClass, $updateRequestClass] = $this->generateFormRequests(
                $modelClass, $storeRequestClass, $updateRequestClass
            );
        }

        $namespacedRequests = $storeRequestClass.';';

        if ($storeRequestClass !== $updateRequestClass) {
            $namespacedRequests .= PHP_EOL.'use '.$updateRequestClass.';';
        }

        return array_merge($replace, [
            '{{ storeRequest }}'            => class_basename($storeRequestClass),
            '{{storeRequest}}'              => class_basename($storeRequestClass),
            '{{ updateRequest }}'           => class_basename($updateRequestClass),
            '{{updateRequest}}'             => class_basename($updateRequestClass),
            '{{ namespacedStoreRequest }}'  => $storeRequestClass,
            '{{namespacedStoreRequest}}'    => $storeRequestClass,
            '{{ namespacedUpdateRequest }}' => $updateRequestClass,
            '{{namespacedUpdateRequest}}'   => $updateRequestClass,
            '{{ namespacedRequests }}'      => $namespacedRequests,
            '{{namespacedRequests}}'        => $namespacedRequests,
        ]);
    }

    /**
     * Generate the fully qualified class names for the Store and Update Form Request classes.
     *
     * ⚠️ Overridden: This method changes the behavior from the parent class.
     * In the base implementation, the request namespace is hardcoded within
     * `buildFormRequestReplacements()` (e.g., 'App\\Http\\Requests'), and the
     * $storeRequestClass and $updateRequestClass arguments are overwritten directly.
     *
     * Here, we use a dynamic, module-based namespace to better support
     * modular architectures.
     *
     * ⚠️ Compatibility Note:
     * If the framework updates this logic to actually honor the passed parameters,
     * this override may need to be revised or removed to maintain compatibility.
     *
     * @param string $modelClass The model class name
     * @param string $storeRequestClass The store request class name
     * @param string $updateRequestClass The update request class name
     * @return array The generated request class names
     */
    protected function generateFormRequests($modelClass, $storeRequestClass, $updateRequestClass)
    {
        // NOTE: In the base class, the namespace is hardcoded in:
        // buildFormRequestReplacements() → $namespace = 'App\\Http\\Requests';

        // We override this here to resolve the namespace dynamically from the module structure.

        // ⚠️ If the parent class or framework changes its expectations for
        // request class locations, review this override accordingly.

        // We apply a dynamic module-aware namespace instead:
        $namespace = $this->moduleNamespace('request', 'Presentation/Http/Requests');

        $storeRequestClass  = $namespace.'\\'.'Store'.class_basename($modelClass).'Request';
        $updateRequestClass = $namespace.'\\'.'Update'.class_basename($modelClass).'Request';

        if (! $this->shouldUseModularRequests()) {
            $this->call('make:request', [
                'name' => $storeRequestClass,
            ]);

            $this->call('make:request', [
                'name' => $updateRequestClass,
            ]);
        } else {
            $this->call('easymodules:make-request', [
                'module' => $this->getModuleInput(),
                'name'   => 'Store'.class_basename($modelClass).'Request',
            ]);

            $this->call('easymodules:make-request', [
                'module' => $this->getModuleInput(),
                'name'   => 'Update'.class_basename($modelClass).'Request',
            ]);
        }

        return [$storeRequestClass, $updateRequestClass];
    }

    /**
     * Determine if modular request generation should be used.
     *
     * Returns true when the module's root namespace falls outside Laravel's
     * root namespace, indicating that Laravel's qualifyClass() method would
     * generate incorrect paths for the modular architecture.
     *
     * @return bool
     */
    protected function shouldUseModularRequests(): bool
    {
        $laravelRootNamespace = $this->laravel->getNamespace(); // e.g., "App\"
        $moduleRootNamespace  = $this->rootModuleNamespace();    // e.g., "App\Modules\Blog\" or "Custom\Modules\Blog\"

        // Use modular commands when module namespace is outside Laravel's root namespace
        return ! Str::startsWith($moduleRootNamespace, $laravelRootNamespace);
    }
}
