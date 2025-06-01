# Configuration Guide - Laravel Easy Modules

> **Complete customization guide for Laravel Easy Modules modular architecture patterns**

## 🎯 Overview

Laravel Easy Modules is designed to be **fully customizable** to your project needs. While it provides Clean Architecture as a sensible default, you can configure any folder structure and architectural pattern that fits your requirements.

> **Package Status**: This package has been tested and works correctly for most use cases. However, some edge cases may require manual handling depending on your specific setup.

## 📋 Table of Contents

- [📁 Basic Configuration](#-basic-configuration)
- [🏗️ Module Structure Customization](#️-module-structure-customization)
- [🎨 Component Path Mapping](#-component-path-mapping)
- [📝 Custom Stubs & Templates](#-custom-stubs--templates)
- [🧪 Testing Configuration](#-testing-configuration)
- [🔧 Advanced Options](#-advanced-options)
- [📖 Architecture Examples](#-architecture-examples)

---

## 📁 Basic Configuration

### Publishing Configuration

```bash
# Publish the configuration file
php artisan easymodules:publish

# Or publish with vendor:publish
php artisan vendor:publish --provider="Kadevland\EasyModules\EasyModulesServiceProvider" --tag="config"
```

The configuration file will be published to `config/easymodules.php`.

### Core Settings

```php
// config/easymodules.php
return [
    // Where modules will be created
    'base_path' => app_path('Modules'),
    
    // Root namespace for all modules
    'base_namespace' => 'App\\Modules',
    
    // Auto-register modules with Laravel
    'auto_discover' => true,
];
```

#### Changing Module Location

```php
// Example: Move modules to a different location
'base_path' => base_path('src/Modules'),
'base_namespace' => 'Src\\Modules',

// Or organize by domain
'base_path' => app_path('Domain'),
'base_namespace' => 'App\\Domain',
```

---

## 🏗️ Module Structure Customization

### Default Clean Architecture Structure

The `folders_to_generate` array defines which directories will be automatically created when you run `php artisan easymodules:new ModuleName`. This is the folder skeleton of your module.

```php
'folders_to_generate' => [
    // ⚙️ APPLICATION LAYER
    'Application/Actions',          // Use case implementations
    'Application/DTOs',             // Data Transfer Objects
    'Application/Services',         // Application services
    'Application/Validation',       // Business rules validation

    // 🧠 DOMAIN LAYER
    'Domain/Entities',              // Domain entities
    'Domain/Services',              // Domain services
    'Domain/ValueObjects',          // Value objects

    // 🏛️ INFRASTRUCTURE LAYER
    'Infrastructure/Models',        // Eloquent models
    'Infrastructure/Persistences',  // Repositories
    'Infrastructure/Services',      // External services

    // 🎨 PRESENTATION LAYER
    'Presentation/Http/Controllers',// HTTP controllers
    'Presentation/Http/Requests',   // Form requests
    'Presentation/Http/Resources',  // API resources

    // 🗄️ DATABASE LAYER
    'Database/Migrations',          // Database migrations
    'Database/Factories',           // Model factories
    'Database/Seeders',             // Database seeders

    // 🧪 TESTING
    'Tests/Unit',                   // Unit tests
    'Tests/Feature',                // Feature tests
],
```

### Module Structure vs Component Paths

```php
// folders_to_generate: Created during module creation
'folders_to_generate' => [
    'Application/Services',
    'Domain/Entities',
    // ... other folders
],

// paths: Used by make commands (folders created on-demand)
'paths' => [
    'entity' => 'Domain/Entities',          // Uses existing folder
    'helper' => 'Application/Helpers',      // Creates folder if needed
    'custom' => 'Custom/Components',        // Creates new structure
],
```

### Alternative Architecture Examples

#### Traditional MVC Structure

```php
'folders_to_generate' => [
    'Controllers',
    'Models',
    'Views',
    'Services',
    'Helpers',
    'Requests',
    'Resources',
    'Tests',
],
```

#### Domain-Driven Design (DDD)

```php
'folders_to_generate' => [
    'Domain/Aggregates',
    'Domain/ValueObjects',
    'Domain/Events',
    'Application/Commands',
    'Application/Queries',
    'Application/Handlers',
    'Infrastructure/Persistence',
    'Infrastructure/Projections',
    'Tests/Unit',
    'Tests/Integration',
],
```

#### Feature-Based Structure

```php
'folders_to_generate' => [
    'Features/Auth',
    'Features/Blog',
    'Features/Shop',
    'Shared/Services',
    'Shared/Models',
    'Tests',
],
```

---

## 🎨 Component Path Mapping

The `paths` configuration determines where specific component types are generated within your modules when using make commands like `php artisan easymodules:make-controller` or `php artisan easymodules:make-entity`.

> **💡 Important**: Paths defined here don't need to exist in `folders_to_generate`. The required folder will be automatically created when you run the make command.

Configure where each component type should be generated within modules:

### Default Clean Architecture Paths

```php
'paths' => [
    // Domain Layer
    'entity'         => 'Domain/Entities',
    'valueobject'    => 'Domain/ValueObjects',
    
    // Application Layer
    'action'         => 'Application/Actions',
    'dto'            => 'Application/DTOs',
    
    // Infrastructure Layer
    'model'          => 'Infrastructure/Models',
    'repository'     => 'Infrastructure/Persistences/Repositories',
    'job'            => 'Infrastructure/Jobs',
    'event'          => 'Infrastructure/Events',
    'listener'       => 'Infrastructure/Listeners',
    
    // Presentation Layer
    'controller'     => 'Presentation/Http/Controllers',
    'request'        => 'Presentation/Http/Requests',
    'resource'       => 'Presentation/Http/Resources',
    'middleware'     => 'Presentation/Http/Middlewares',
    'command'        => 'Presentation/Console/Commands',
    
    // Database Layer
    'migration'      => 'Database/Migrations',
    'factory'        => 'Database/Factories',
    'seeder'         => 'Database/Seeders',
],
```

### Customizing for Traditional MVC

```php
'paths' => [
    'controller'     => 'Controllers',
    'model'          => 'Models',
    'request'        => 'Requests',
    'resource'       => 'Resources',
    'middleware'     => 'Middleware',
    'job'            => 'Jobs',
    'event'          => 'Events',
    'listener'       => 'Listeners',
    'migration'      => 'Migrations',
    'factory'        => 'Factories',
    'seeder'         => 'Seeders',
],
```

### Domain-Driven Design Paths

```php
'paths' => [
    'entity'         => 'Domain/Aggregates',
    'valueobject'    => 'Domain/ValueObjects',
    'repository'     => 'Infrastructure/Persistence',
    'event'          => 'Domain/Events',
    'command'        => 'Application/Commands',
    'handler'        => 'Application/Handlers',
    'query'          => 'Application/Queries',
],
```

---

## 📝 Custom Stubs & Templates

The `stubs` configuration defines which template files are used when generating components. Each component type can have its own custom template to match your coding standards and architecture patterns.

> **💡 Important**: For Laravel's base component types (controller, model, request, etc.), if no custom stub is defined, Laravel Easy Modules will automatically use Laravel's default stubs as fallback.

### Publishing Stubs

```bash
# Publish stubs for customization
php artisan easymodules:publish --stubs
```

Stubs will be published to `resources/stubs/easymodules/`.

### Default Stub Configuration

```php
'stubs' => [
    // Domain Layer
    'entity'         => 'easymodules/entity.stub',
    'valueobject'    => 'easymodules/valueobject.stub',
    
    // Infrastructure Layer
    'model'          => 'easymodules/model.stub',
    'repository'     => 'easymodules/repository.stub',
    'controller'     => 'easymodules/controller.stub',
    
    // Application Layer
    'dto'            => 'easymodules/dto.stub',
    'action'         => 'easymodules/action.stub',
],
```

### Creating Custom Stubs

Create your own stub templates in `resources/stubs/easymodules/`:

```php
// resources/stubs/easymodules/custom-service.stub
<?php

namespace {{ namespace }};

class {{ class }}
{
    public function __construct()
    {
        // Your custom service template
    }
    
    public function handle(): void
    {
        // Custom logic here
    }
}
```

Then reference it in configuration:

```php
'stubs' => [
    'service' => 'easymodules/custom-service.stub',
],
```

### Flexible Stub System

**Important**: No stubs are provided by default - it's up to the developer to create them according to their needs.

You can create any component type using the flexible `make-stub` command:

```php
// Add your custom stub types in config/easymodules.php
'stubs' => [
    'dto'            => 'easymodules/dto.stub',
    'valueobject'    => 'easymodules/valueobject.stub',
    'repository'     => 'easymodules/repository.stub',
    'mapper'         => 'easymodules/mapper.stub',
    'specification'  => 'easymodules/specification.stub',
],

'paths' => [
    'dto'            => 'Application/DTOs',
    'valueobject'    => 'Domain/ValueObjects',
    'repository'     => 'Infrastructure/Persistences/Repositories',
    'mapper'         => 'Application/Mappers',
    'specification'  => 'Domain/Specifications',
],
```

Then use the flexible generation system:

```bash
# Create custom components
php artisan easymodules:make-stub Blog CreatePostDTO dto
php artisan easymodules:make-stub Shop Money valueobject
php artisan easymodules:make-stub User UserRepository repository
```

### Scaffold Templates

Customize the files generated during module creation:

```php
'stubs_scaffold' => [
    'config'           => 'easymodules/scaffold/config.stub',
    'service_provider' => 'easymodules/scaffold/service_provider.stub',
    'route_web'        => 'easymodules/scaffold/route_web.stub',
    'route_api'        => 'easymodules/scaffold/route_api.stub',
    'route_console'    => 'easymodules/scaffold/route_console.stub',
],
```

---

## 🧪 Testing Configuration

### Test Path Mapping

Configure where tests should be generated:

```php
'test_paths' => [
    // Component tests
    'controller'     => 'Presentation/Http/Controllers',
    'entity'         => 'Domain/Entities',
    'model'          => 'Infrastructure/Models',
    
    // Shortcuts for quick generation
    'c'              => 'Presentation/Http/Controllers',
    'e'              => 'Domain/Entities',
],
```

### Usage Examples

```bash
# Generate test in specific path
php artisan easymodules:make-test Blog PostServiceTest --path=Services --unit

# Use shortcut
php artisan easymodules:make-test Blog PostControllerTest --path=c

# Custom path
php artisan easymodules:make-test Blog CustomTest --path=Custom/Logic --unit
```

---

## 🔧 Advanced Options

### Auto-Suffix Management

```php
// Enable automatic suffix appending
'append_suffix' => true,

// Define suffixes for each component type
'suffixes' => [
    'model'          => 'Model',
    'controller'     => 'Controller',
    'repository'     => 'Repository',
    'dto'            => 'DTO',
    'valueobject'    => 'ValueObject',
],
```

**Example with suffixes enabled:**
```bash
php artisan easymodules:make-entity Blog Post
# Generates: PostEntity.php (suffix auto-added)
```

### Module Auto-Discovery

```php
// Enable/disable automatic module registration
'auto_discover' => true,

// When enabled, modules are automatically:
// - Added to bootstrap/providers.php
// - Loaded on application startup
// - Available immediately after creation
```

### Scaffold Folders

The `scaffold` array defines the essential folders that are **always** created during module generation, regardless of your architecture. These contain the core files needed for any functional module (ServiceProvider, config, routes).

```php
'scaffold' => [
    'Providers',                    // Folder for service providers
    'config',                       // Folder for configuration files
    'routes',                       // Folder for route definitions
],
```

### Scaffold Templates

The `stubs_scaffold` configuration specifies which templates are used to generate the essential operational files that make your module functional within Laravel:

```php
'stubs_scaffold' => [
    'config'           => 'easymodules/scaffold/config.stub',           // Module configuration file
    'service_provider' => 'easymodules/scaffold/service_provider.stub', // Auto-registered ServiceProvider
    'route_web'        => 'easymodules/scaffold/route_web.stub',        // Web routes with examples
    'route_api'        => 'easymodules/scaffold/route_api.stub',        // API routes with examples
    'route_console'    => 'easymodules/scaffold/route_console.stub',    // Console routes with examples
],
```

---

## 📖 Architecture Examples

### Example 1: Microservice Architecture

```php
// config/easymodules.php
return [
    'base_path' => app_path('Services'),
    'base_namespace' => 'App\\Services',
    
    'folders_to_generate' => [
        'Api/Controllers',
        'Api/Requests',
        'Api/Resources',
        'Business/Services',
        'Business/Models',
        'Infrastructure/Repositories',
        'Infrastructure/External',
        'Tests/Api',
        'Tests/Business',
    ],
    
    'paths' => [
        'controller' => 'Api/Controllers',
        'request'    => 'Api/Requests', 
        'resource'   => 'Api/Resources',
        'model'      => 'Business/Models',
        'repository' => 'Infrastructure/Repositories',
    ],
];
```

### Example 2: Vertical Slice Architecture

```php
// config/easymodules.php
return [
    'folders_to_generate' => [
        'Features/CreatePost',
        'Features/UpdatePost',
        'Features/DeletePost',
        'Features/ListPosts',
        'Shared/Models',
        'Shared/Services',
        'Tests/Features',
    ],
    
    'paths' => [
        'feature'    => 'Features',
        'model'      => 'Shared/Models',
    ],
];
```

### Example 3: Hexagonal Architecture

```php
// config/easymodules.php
return [
    'folders_to_generate' => [
        'Core/Domain',
        'Core/Application',
        'Adapters/Primary/Web',
        'Adapters/Primary/Api',
        'Adapters/Secondary/Database',
        'Adapters/Secondary/External',
        'Tests/Unit',
        'Tests/Integration',
    ],
    
    'paths' => [
        'entity'     => 'Core/Domain',
        'controller' => 'Adapters/Primary/Web',
        'api'        => 'Adapters/Primary/Api',
        'repository' => 'Adapters/Secondary/Database',
    ],
];
```

---

## 💡 Best Practices

### 1. **Start Simple, Evolve Gradually**

```php
// Start with basic structure
'folders_to_generate' => [
    'Controllers',
    'Models', 
    'Services',
    'Tests',
],

// Evolve to Clean Architecture when ready
'folders_to_generate' => [
    'Application/Services',
    'Domain/Entities',
    'Infrastructure/Models',
    'Presentation/Http/Controllers',
    // ...
],
```

### 2. **Consistent Team Conventions**

```php
// Establish team naming conventions
'suffixes' => [
    'repository' => 'Repository',   // UserRepository
    'dto'        => 'Data',         // UserData
],
```

### 3. **Environment-Specific Configuration**

```php
// config/easymodules.php
return [
    'base_path' => env('MODULES_PATH', app_path('Modules')),
    'auto_discover' => env('MODULES_AUTO_DISCOVER', true),
];
```

### 4. **Documentation in Configuration**

```php
return [
    /*
    |--------------------------------------------------------------------------
    | Your Custom Architecture Pattern
    |--------------------------------------------------------------------------
    |
    | Document your architecture decisions and folder purposes here.
    | This helps new team members understand the structure.
    |
    */
    
    'folders_to_generate' => [
        'UseCases',      // Business logic implementations
        'Entities',      // Domain models
        'Gateways',      // Data access interfaces
        // ...
    ],
];
```

---

## 🚀 Quick Configuration Templates

### Copy & Paste Configurations

#### Clean Architecture (Default)
```php
'folders_to_generate' => [
    'Application/Services', 'Application/DTOs', 'Application/Actions',
    'Domain/Entities', 'Domain/Services', 'Domain/ValueObjects',
    'Infrastructure/Models', 'Infrastructure/Persistences', 'Infrastructure/Services',
    'Presentation/Http/Controllers', 'Presentation/Http/Requests', 'Presentation/Http/Resources',
    'Database/Migrations', 'Database/Factories', 'Database/Seeders',
    'Tests/Unit', 'Tests/Feature',
],
```

#### Traditional MVC
```php
'folders_to_generate' => [
    'Controllers', 'Models', 'Views', 'Services', 'Requests', 'Resources',
    'Middleware', 'Jobs', 'Events', 'Listeners', 'Notifications',
    'Migrations', 'Factories', 'Seeders', 'Tests',
],
```

#### DDD (Domain-Driven Design)
```php
'folders_to_generate' => [
    'Domain/Aggregates', 'Domain/ValueObjects', 'Domain/Events', 'Domain/Services',
    'Application/Commands', 'Application/Queries', 'Application/Handlers',
    'Infrastructure/Persistence', 'Infrastructure/Projections', 'Infrastructure/External',
    'Tests/Unit', 'Tests/Integration',
],
```

---

**Remember**: Laravel Easy Modules is designed to adapt to **your** architecture, not impose one. Start with what works for your team and evolve as needed! 🚀
