<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Base Path for Modules
    |--------------------------------------------------------------------------
    |
    | This is the root path where all your modules will be created. Each module's
    | classes, controllers, models, etc., will be generated within this path.
    |
    */

    'base_path'           => app_path('Modules'),

    /*
    |--------------------------------------------------------------------------
    | Base Namespace for Modules
    |--------------------------------------------------------------------------
    |
    | This namespace will be used as the root for all your modules. All generated
    | classes will use this namespace prefix.
    |
    */

    'base_namespace'      => 'App\\Modules',

    /*
    |--------------------------------------------------------------------------
    | Module Auto-Discovery
    |--------------------------------------------------------------------------
    |
    | When enabled, the package will automatically detect and register modules
    | based on their presence in the base path. Disable this if you prefer manual
    | registration of modules.
    |
    */

    'auto_discover'       => true,

    /*
    |--------------------------------------------------------------------------
    | Module Folders to Generate
    |--------------------------------------------------------------------------
    |
    | This array defines the list of folders that should be automatically
    | created when a new module is generated using the easymodules:new command.
    | These folders will be part of your module structure following Clean Architecture.
    |
    */

    'folders_to_generate' => [

        // ⚙️ APPLICATION LAYER
        'Application/Actions',          // Actions for Use Case Management
        'Application/DTOs',             // Data Transfer Objects for communication between layers
        'Application/Mappers',          // Mappers for converting between entities and DTOs
        'Application/Interfaces',       // Interfaces for communication with external services
        'Application/Services',         // Services for use cases
        'Application/Validation',       // Validation of incoming data
        'Application/Rules',            // Reusable business rules

        // 🧠 DOMAIN LAYER
        'Domain/Entities',              // Domain entities (business models)
        'Domain/Services',              // Business services in the domain
        'Domain/ValueObjects',          // Value objects used in entities

        // 🏛️ INFRASTRUCTURE LAYER
        'Infrastructure/Mappers',       // Mappers for transformation between entities and models
        'Infrastructure/Models',        // Persistence-related models (database)
        'Infrastructure/Casts',         // Custom casts for models
        'Infrastructure/Persistence',  // Data persistence implementations, repositories
        'Infrastructure/Services',      // External technical or integration services
        'Infrastructure/Exceptions',    // Custom exceptions for error handling

        // 🎨 PRESENTATION LAYER
        'Presentation/Mappers',         // Mappers for display-related transformations
        'Presentation/Console/Commands',// Custom commands via Artisan
        'Presentation/Http/Controllers',// HTTP controllers for request handling
        'Presentation/Http/Requests',   // HTTP requests to validate and transform data
        'Presentation/Http/Middlewares',// Middleware to filter incoming requests
        'Presentation/Http/Resources',  // Resources for formatting responses
        'Presentation/Views/Components',// View components for the UI
        'Presentation/resources/views', // Folder containing views (e.g., Blade)

        // 🗄️ DATABASE LAYER
        'Database/Factories',           // Model factories for testing
        'Database/Seeders',             // Database seeders
        'Database/Migrations',          // Migrations for database schema management

        // 🌍 LOCALIZATION
        'lang',                         // Translation files

        // 🧪 TESTING
        'Tests/Unit',                   // Unit testing
        'Tests/Feature',                // Feature/Integration tests
    ],

    /*
    |--------------------------------------------------------------------------
    | Module Scaffold Folders
    |--------------------------------------------------------------------------
    |
    | This array defines the essential folders that will be created in addition
    | to the folders_to_generate when running the module creation command.
    | These are the base folders needed for scaffold components.
    |
    */

    'scaffold'            => [
        'Providers',                    // Folder for service providers
        'config',                       // Folder for configuration files
        'routes',                       // Folder for route definitions
    ],

    /*
    |--------------------------------------------------------------------------
    | Component Path Mapping
    |--------------------------------------------------------------------------
    |
    | Each key represents a file type supported by Laravel's generators.
    | The corresponding value is the relative path (inside the module) where
    | the file should be placed when generated. Used by make commands.
    |
    */

    'paths'               => [
        // 🏗️ SCAFFOLD COMPONENTS
        'provider'       => 'Providers',
        'config'         => 'config',
        'routes'         => 'routes',

        // 🧠 DOMAIN LAYER
        'entity'         => 'Domain/Entities',
        'valueobject'    => 'Domain/ValueObjects',

        // 🏛️ INFRASTRUCTURE LAYER
        'repository'     => 'Infrastructure/Persistence/Repositories',
        'model'          => 'Infrastructure/Models',
        'scope'          => 'Infrastructure/Models/Scopes',
        'cast'           => 'Infrastructure/Casts',
        'job'            => 'Infrastructure/Jobs',
        'job-middleware' => 'Infrastructure/Jobs/Middlewares',
        'event'          => 'Infrastructure/Events',
        'listener'       => 'Infrastructure/Listeners',
        'mail'           => 'Infrastructure/Mails',
        'notification'   => 'Infrastructure/Notifications',
        'policy'         => 'Infrastructure/Policies',
        'rule'           => 'Infrastructure/Rules',
        'observer'       => 'Infrastructure/Observers',
        'channel'        => 'Infrastructure/Broadcasting',
        'exception'      => 'Infrastructure/Exceptions',

        // 🎨 PRESENTATION LAYER
        'command'        => 'Presentation/Console/Commands',
        'controller'     => 'Presentation/Http/Controllers',
        'request'        => 'Presentation/Http/Requests',
        'middleware'     => 'Presentation/Http/Middlewares',
        'resource'       => 'Presentation/Http/Resources',
        'component'      => 'Presentation/Views/Components',
        'view'           => 'Presentation/resources/views',

        // 🗄️ DATABASE LAYER
        'migration'      => 'Database/Migrations',
        'seeder'         => 'Database/Seeders',
        'factory'        => 'Database/Factories',

        // 🌍 LOCALIZATION
        'lang'           => 'lang',

        // 🧪 TESTING
        'unittest'       => 'Tests/Unit',
        'featuretest'    => 'Tests/Feature',
    ],

    /*
    |--------------------------------------------------------------------------
    | Test Path Mapping
    |--------------------------------------------------------------------------
    |
    | Each key represents a component type that can generate tests with --test option.
    | The corresponding value is the relative path (inside Tests/Feature or Tests/Unit)
    | where the test should be placed. Used by make commands with --test flag.
    | If a component type is not found, the key is used as-is for custom paths.
    |
    */

    'test_paths'          => [

        // 🧠 DOMAIN LAYER
        'entity'       => 'Domain/Entities',
        'valueobject'  => 'Domain/ValueObjects',

        // 🏛️ INFRASTRUCTURE LAYER
        'repository'   => 'Infrastructure/Persistence/Repositories',
        'model'        => 'Infrastructure/Models',
        'scope'        => 'Infrastructure/Models/Scopes',
        'cast'         => 'Infrastructure/Casts',
        'job'          => 'Infrastructure/Jobs',
        'event'        => 'Infrastructure/Events',
        'listener'     => 'Infrastructure/Listeners',
        'mail'         => 'Infrastructure/Mails',
        'notification' => 'Infrastructure/Notifications',
        'policy'       => 'Infrastructure/Policies',
        'rule'         => 'Infrastructure/Rules',
        'observer'     => 'Infrastructure/Observers',
        'channel'      => 'Infrastructure/Broadcasting',
        'exception'    => 'Infrastructure/Exceptions',

        // 🎨 PRESENTATION LAYER
        'command'      => 'Presentation/Console/Commands',
        'controller'   => 'Presentation/Http/Controllers',
        'request'      => 'Presentation/Http/Requests',
        'middleware'   => 'Presentation/Http/Middlewares',
        'resource'     => 'Presentation/Http/Resources',
        'component'    => 'Presentation/Views/Components',
        'view'         => 'Presentation/resources/views',

        // Shortcuts
        'c'            => 'Presentation/Http/Controllers',
        's'            => 'Application/Services',
        'e'            => 'Domain/Entities',
    ],

    /*
    |--------------------------------------------------------------------------
    | Append Suffixes to Class Names
    |--------------------------------------------------------------------------
    |
    | When enabled, the generator will append appropriate suffixes (e.g., 'Controller',
    | 'Model') to class names based on their type. This helps maintain consistency
    | in naming conventions across your application.
    |
    */

    'append_suffix'       => false,

    /*
    |--------------------------------------------------------------------------
    | Class Name Suffixes
    |--------------------------------------------------------------------------
    |
    | This array defines the suffixes to be appended to class names for each
    | type of class. For example, a 'model' type will have 'Model' appended,
    | resulting in 'UserModel' if the base name is 'User'.
    |
    */

    'suffixes'            => [
        'model'        => 'Model',
        'controller'   => 'Controller',
        'request'      => 'Request',
        'resource'     => 'Resource',
        'cast'         => 'Cast',
        'factory'      => 'Factory',
        'seeder'       => 'Seeder',
        'migration'    => 'Migration',
        'middleware'   => 'Middleware',
        'command'      => 'Command',
        'job'          => 'Job',
        'event'        => 'Event',
        'listener'     => 'Listener',
        'mail'         => 'Mail',
        'notification' => 'Notification',
        'policy'       => 'Policy',
        'provider'     => 'Provider',
        'rule'         => 'Rule',
        'observer'     => 'Observer',
        'channel'      => 'Channel',
        'exception'    => 'Exception',
        'entity'       => 'Entity',
        'interface'    => 'Interface',
        'scope'        => 'Scope',
        'component'    => 'Component',
    ],

    /*
    |--------------------------------------------------------------------------
    | Scaffold Template Files
    |--------------------------------------------------------------------------
    |
    | This array defines the paths to stub files that will be used to generate
    | the base files within the scaffold folders. These templates create the
    | essential files needed to initialize a functional module (ServiceProvider,
    | config, routes).
    |
    */

    'stubs_scaffold'      => [
        'config'           => 'easymodules/scaffold/config.stub',
        'service_provider' => 'easymodules/scaffold/service_provider.stub',
        'route_web'        => 'easymodules/scaffold/route_web.stub',
        'route_api'        => 'easymodules/scaffold/route_api.stub',
        'route_console'    => 'easymodules/scaffold/route_console.stub',
    ],

    /*
    |--------------------------------------------------------------------------
    | Component Template Files
    |--------------------------------------------------------------------------
    |
    | This array defines the path to stub files for each type of class.
    | These paths should be relative to the resources/stubs directory
    | or absolute if needed. Used by individual make commands.
    |
    */

    'stubs'               => [

        // 🏗️ SCAFFOLD COMPONENTS
        'provider'       => 'easymodules/provider.stub',
        'config'         => 'easymodules/config.stub',

        // 🧠 DOMAIN LAYER
        'entity'         => 'easymodules/entity.stub',
        'valueobject'    => 'easymodules/valueobject.stub',
        'domainservice'  => 'easymodules/domain-service.stub',

        // ⚙️ APPLICATION LAYER
        'action'         => 'easymodules/action.stub',
        'dto'            => 'easymodules/dto.stub',
        'mapper'         => 'easymodules/mapper.stub',
        'interface'      => 'easymodules/interface.stub',
        'service'        => 'easymodules/service.stub',
        'validation'     => 'easymodules/validation.stub',

        // 🏛️ INFRASTRUCTURE LAYER
        'repository'     => 'easymodules/repository.stub',
        'persistence'    => 'easymodules/persistence.stub',
        'model'          => 'easymodules/model.stub',
        'cast'           => 'easymodules/cast.stub',
        'factory'        => 'easymodules/factory.stub',
        'seeder'         => 'easymodules/seeder.stub',
        'migration'      => 'easymodules/migration.stub',
        'middleware'     => 'easymodules/middleware.stub',
        'command'        => 'easymodules/command.stub',
        'job'            => 'easymodules/job.stub',
        'job-middleware' => 'easymodules/job.middleware.stub',
        'event'          => 'easymodules/event.stub',
        'listener'       => 'easymodules/listener.stub',
        'mail'           => 'easymodules/mail.stub',
        'notification'   => 'easymodules/notification.stub',
        'policy'         => 'easymodules/policy.stub',
        'rule'           => 'easymodules/rule.stub',
        'observer'       => 'easymodules/observer.stub',
        'channel'        => 'easymodules/channel.stub',
        'exception'      => 'easymodules/exception.stub',

        // 🎨 PRESENTATION LAYER
        'controller'     => 'easymodules/controller.stub',
        'request'        => 'easymodules/request.stub',
        'resource'       => 'easymodules/resource.stub',
        'component'      => 'easymodules/component.stub',

        // 🧪 TESTING LAYER
        'test'           => 'easymodules/test.stub',
        'featuretest'    => 'easymodules/feature-test.stub',

    ],

];
