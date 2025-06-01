# Architecture Templates - Laravel Easy Modules v2.0

> **🚀 Future Feature**: Multi-template architecture system for Laravel Easy Modules, allowing different architectural patterns per module with modular configuration files.

## 🎯 Overview

The template management system allows developers to choose between different architecture patterns when creating modules, making EasyModules truly universal for any Laravel project structure. Each template is completely self-contained with its own configuration file.

## 🏗️ Architecture Philosophy

### **Current v1.0 Approach**

-   Single configuration in `config/easymodules.php`
-   Clean Architecture shipped as default structure
-   Global configuration applied to all modules

### **Future v2.0 Approach**

-   **Modular configuration files** per template
-   **Template-specific** folder structures, paths, and stubs
-   **Per-module template selection** with individual configuration
-   **Extensible system** for third-party templates

---

## 📋 Template System Architecture

### **Configuration Structure**

```
config/
├── easymodules.php                         # Base configuration (backward compatible)
├── easymodules-template-mvc.php            # MVC template (dedicated file)
├── easymodules-template-ddd.php            # DDD template (dedicated file)
├── easymodules-template-event-sourcing.php # Event Sourcing template (dedicated file)
├── easymodules-template-laravel.php        # Laravel Standard template (dedicated file)
├── easymodules-template-business-logic.php # Custom Business template (dedicated file)
├── easymodules-template-custom.php         # Your custom template (any architecture you want)
└── easymodules-templates.php               # Fallback for built-in templates (optional)
```

### **Template Resolution Logic**

```php
// Template resolution priority:
// 1. Check config/easymodules-template-{name}.php (highest priority)
// 2. Check config/easymodules-templates.php[{name}] (fallback)
// 3. Error if template not found

// Example for --template=mvc:
// 1. Look for config/easymodules-template-mvc.php
// 2. If not found, look for config/easymodules-templates.php['mvc']
// 3. If not found, throw template not found error
```

---

## 🎨 Template Examples

### **MVC Template**

```php
// config/easymodules-template-mvc.php
return [
    'name' => 'MVC',
    'label' => 'Traditional Model-View-Controller Pattern',

    'folders_to_generate' => [
        'Controllers',
        'Models',
        'Views',
        'Services',
        'Helpers',
        'Requests',
        'Resources',
        'Middleware',
        'Jobs',
        'Events',
        'Listeners',
        'Migrations',
        'Factories',
        'Seeders',
        'Tests',
    ],

    'paths' => [
        'controller'     => 'Controllers',
        'model'          => 'Models',
        'service'        => 'Services',
        'request'        => 'Requests',
        'resource'       => 'Resources',
        'middleware'     => 'Middleware',
        'job'            => 'Jobs',
        'event'          => 'Events',
        'listener'       => 'Listeners',
        'migration'      => 'Migrations',
        'factory'        => 'Factories',
        'seeder'         => 'Seeders',
        'test'           => 'Tests',
    ],

    'stubs' => [
        'controller'     => 'mvc/controller.stub',
        'model'          => 'mvc/model.stub',
        'service'        => 'mvc/service.stub',
        'request'        => 'mvc/request.stub',
        'resource'       => 'mvc/resource.stub',
    ],
];
```

### **Domain-Driven Design Template**

```php
// config/easymodules-template-ddd.php
return [
    'name' => 'DDD',
    'label' => 'Domain-Driven Design Architecture',

    'folders_to_generate' => [
        'Domain/Aggregates',
        'Domain/ValueObjects',
        'Domain/Events',
        'Domain/Services',
        'Application/Commands',
        'Application/Queries',
        'Application/Handlers',
        'Application/Services',
        'Infrastructure/Persistence',
        'Infrastructure/Projections',
        'Infrastructure/External',
        'Tests/Unit',
        'Tests/Integration',
    ],

    'paths' => [
        'aggregate'      => 'Domain/Aggregates',
        'valueobject'    => 'Domain/ValueObjects',
        'domain-service' => 'Domain/Services',
        'event'          => 'Domain/Events',
        'command'        => 'Application/Commands',
        'query'          => 'Application/Queries',
        'handler'        => 'Application/Handlers',
        'service'        => 'Application/Services',
        'repository'     => 'Infrastructure/Persistence',
        'projection'     => 'Infrastructure/Projections',
    ],

    'stubs' => [
        'aggregate'      => 'ddd/aggregate.stub',
        'valueobject'    => 'ddd/valueobject.stub',
        'command'        => 'ddd/command.stub',
        'handler'        => 'ddd/handler.stub',
        'repository'     => 'ddd/repository.stub',
    ],
];
```

### **Event Sourcing + CQRS Template**

```php
// config/easymodules-template-event-sourcing.php
return [
    'name' => 'EventSourcing',
    'label' => 'Event Sourcing with CQRS Architecture',

    'folders_to_generate' => [
        'Domain/Events',
        'Domain/Aggregates',
        'Domain/ValueObjects',
        'Application/Commands',
        'Application/CommandHandlers',
        'Application/Queries',
        'Application/QueryHandlers',
        'Application/Projectors',
        'Application/Reactors',
        'Infrastructure/EventStore',
        'Infrastructure/Projections',
        'Infrastructure/ReadModels',
        'Infrastructure/Snapshots',
        'ReadModels/Views',
        'WriteModels/Aggregates',
        'Tests/Unit',
        'Tests/Integration',
    ],

    'paths' => [
        'event'           => 'Domain/Events',
        'aggregate'       => 'Domain/Aggregates',
        'valueobject'     => 'Domain/ValueObjects',
        'command'         => 'Application/Commands',
        'command-handler' => 'Application/CommandHandlers',
        'query'           => 'Application/Queries',
        'query-handler'   => 'Application/QueryHandlers',
        'projector'       => 'Application/Projectors',
        'reactor'         => 'Application/Reactors',
        'read-model'      => 'Infrastructure/ReadModels',
        'projection'      => 'Infrastructure/Projections',
        'snapshot'        => 'Infrastructure/Snapshots',
    ],

    'stubs' => [
        'event'           => 'event-sourcing/event.stub',
        'aggregate'       => 'event-sourcing/aggregate.stub',
        'command'         => 'event-sourcing/command.stub',
        'command-handler' => 'event-sourcing/command-handler.stub',
        'query'           => 'event-sourcing/query.stub',
        'query-handler'   => 'event-sourcing/query-handler.stub',
        'projector'       => 'event-sourcing/projector.stub',
        'read-model'      => 'event-sourcing/read-model.stub',
    ],
];
```

### **Laravel Standard Template**

```php
// config/easymodules-template-laravel.php
return [
    'name' => 'Laravel',
    'label' => 'Laravel Standard Structure',

    'folders_to_generate' => [
        'Http/Controllers',
        'Http/Middleware',
        'Http/Requests',
        'Http/Resources',
        'Models',
        'Providers',
        'Console/Commands',
        'Events',
        'Listeners',
        'Jobs',
        'Mail',
        'Notifications',
        'Policies',
        'Rules',
        'Observers',
        'Casts',
        'Database/Migrations',
        'Database/Factories',
        'Database/Seeders',
        'Tests/Feature',
        'Tests/Unit',
    ],

    'paths' => [
        'controller'     => 'Http/Controllers',
        'middleware'     => 'Http/Middleware',
        'request'        => 'Http/Requests',
        'resource'       => 'Http/Resources',
        'model'          => 'Models',
        'provider'       => 'Providers',
        'command'        => 'Console/Commands',
        'event'          => 'Events',
        'listener'       => 'Listeners',
        'job'            => 'Jobs',
        'mail'           => 'Mail',
        'notification'   => 'Notifications',
        'policy'         => 'Policies',
        'rule'           => 'Rules',
        'observer'       => 'Observers',
        'cast'           => 'Casts',
        'migration'      => 'Database/Migrations',
        'factory'        => 'Database/Factories',
        'seeder'         => 'Database/Seeders',
    ],

    'stubs' => [
        // Uses Laravel default stubs (no custom stubs needed)
    ],
];
```

### **Your Custom Business Template Example**

> **Note**: This is just an example to demonstrate the flexibility. You can organize and configure any structure you want to fit your specific needs and architectural preferences.

```php
// config/easymodules-template-business-logic.php
return [
    'name' => 'BusinessLogic',
    'label' => 'Business-Focused Modular Architecture',

    'folders_to_generate' => [
        'Business/Services',
        'Business/Rules',
        'Business/Workflows',
        'Business/Validators',
        'Data/Models',
        'Data/Repositories',
        'Data/Transformers',
        'Api/Controllers',
        'Api/Requests',
        'Api/Resources',
        'Web/Controllers',
        'Web/Requests',
        'Integrations/External',
        'Integrations/Webhooks',
        'Reports/Generators',
        'Reports/Exporters',
        'Tests/Business',
        'Tests/Api',
        'Tests/Integration',
    ],

    'paths' => [
        'service'        => 'Business/Services',
        'rule'           => 'Business/Rules',
        'workflow'       => 'Business/Workflows',
        'validator'      => 'Business/Validators',
        'model'          => 'Data/Models',
        'repository'     => 'Data/Repositories',
        'transformer'    => 'Data/Transformers',
        'api-controller' => 'Api/Controllers',
        'api-request'    => 'Api/Requests',
        'api-resource'   => 'Api/Resources',
        'controller'     => 'Web/Controllers',
        'request'        => 'Web/Requests',
        'integration'    => 'Integrations/External',
        'webhook'        => 'Integrations/Webhooks',
        'report'         => 'Reports/Generators',
        'exporter'       => 'Reports/Exporters',
    ],

    'stubs' => [
        'service'        => 'business/service.stub',
        'workflow'       => 'business/workflow.stub',
        'rule'           => 'business/rule.stub',
        'repository'     => 'business/repository.stub',
        'transformer'    => 'business/transformer.stub',
        'integration'    => 'business/integration.stub',
        'report'         => 'business/report.stub',
    ],
];
```

---

## 🚀 Usage Examples

### **Module Creation with Templates**

```bash
# Create module with specific template
php artisan easymodules:new Blog --template=mvc
php artisan easymodules:new OrderManagement --template=ddd
php artisan easymodules:new EventStore --template=event-sourcing
php artisan easymodules:new StandardApp --template=laravel
php artisan easymodules:new CrmModule --template=business-logic

# Create with default template (uses base configuration)
php artisan easymodules:new Legacy
```

### **Module Configuration Storage**

Each module stores its template choice in its configuration:

```php
// app/Modules/Blog/config/config.php (MVC template)
return [
    'name' => 'Blog',
    'template' => 'mvc',

    // Module-specific settings
    'posts_per_page' => 10,
    'cache_ttl' => 3600,
];

// app/Modules/OrderManagement/config/config.php (DDD template)
return [
    'name' => 'OrderManagement',
    'template' => 'ddd',

    // Context-specific settings
    'max_order_items' => 50,
    'payment_timeout' => 300,
];

// app/Modules/Legacy/config/config.php (no template)
return [
    'name' => 'Legacy',
    'template' => '',  // Empty = uses base configuration

    // Legacy settings
    'maintain_compatibility' => true,
];
```

### **Intelligent Command Resolution**

Commands automatically detect the module's template and adapt their behavior:

```bash
# MVC module - uses MVC paths and stubs
php artisan easymodules:make-controller Blog PostController
# → Creates: app/Modules/Blog/Controllers/PostController.php

# DDD module - uses DDD paths and stubs
php artisan easymodules:make-command OrderManagement ProcessOrderCommand
# → Creates: app/Modules/OrderManagement/Application/Commands/ProcessOrderCommand.php

# Event Sourcing module - uses Event Sourcing paths and stubs
php artisan easymodules:make-event EventStore OrderPlaced
# → Creates: app/Modules/EventStore/Domain/Events/OrderPlaced.php

# Laravel Standard module - uses Laravel standard paths
php artisan easymodules:make-controller StandardApp ApiController
# → Creates: app/Modules/StandardApp/Http/Controllers/ApiController.php
```

---

## 🔧 Template Management Commands

### **List Available Templates**

```bash
# List all available templates
php artisan easymodules:list-templates

# Example output:
# Available Architecture Templates:
#
# 🏛️ mvc (MVC)
#    📍 File: config/easymodules-template-mvc.php
#    Traditional Model-View-Controller Pattern
#
# 🎯 ddd (DDD)
#    📍 File: config/easymodules-template-ddd.php
#    Domain-Driven Design Architecture
#
# 🔄 event-sourcing (EventSourcing)
#    📍 File: config/easymodules-template-event-sourcing.php
#    Event Sourcing with CQRS Architecture
#
# ⚙️ laravel (Laravel)
#    📍 File: config/easymodules-template-laravel.php
#    Laravel Standard Structure
#
# 🏢 business-logic (BusinessLogic)
#    📍 File: config/easymodules-template-business-logic.php
#    Business-Focused Modular Architecture
```

### **Template Information**

```bash
# Get detailed template information
php artisan easymodules:template-info mvc

# Example output:
# 📋 Template: MVC
# 📐 Name: Traditional Model-View-Controller Pattern
# 📍 File: config/easymodules-template-mvc.php
#
# 🏗️ Generated Structure:
#   ✅ Controllers/
#   ✅ Models/
#   ✅ Views/
#   ✅ Services/
#   ✅ Tests/
#
# 🎯 Component Paths:
#   📁 controller → Controllers/
#   📁 model → Models/
#   📁 service → Services/
```

### **Publishing Templates**

```bash
# Publish specific template configuration
php artisan easymodules:publish --template=mvc

# Publish specific template stubs
php artisan easymodules:publish --template=mvc --stubs

# Publish all available templates
php artisan easymodules:publish --templates
```

---

## 🌟 Built-in Templates

### **Clean Architecture (Default)**

-   **Current behavior** - shipped in base configuration
-   **Structure**: Domain/Application/Infrastructure/Presentation layers
-   **Use case**: Complex applications with clear business logic separation

### **MVC Traditional**

-   **Structure**: Controllers/Models/Views with supporting folders
-   **Use case**: Standard Laravel applications, prototypes, simple projects

### **Domain-Driven Design (DDD)**

-   **Structure**: Domain/Application/Infrastructure with DDD patterns
-   **Use case**: Complex business domains, enterprise applications

### **Event Sourcing + CQRS**

-   **Structure**: Events/Aggregates + Commands/Queries + Projectors/ReadModels
-   **Use case**: High-volume applications, audit trails, temporal data requirements

### **Laravel**

-   **Structure**: Laravel's default app structure replicated in modules
-   **Use case**: Laravel developers wanting modular organization without learning new patterns

### **Custom Business Logic**

-   **Structure**: Business-focused with API/Web separation and integrations
-   **Use case**: Business applications, CRM systems, enterprise solutions with external integrations

---

## 📦 Third-Party Templates

### **Package-Provided Templates**

Third-party packages can provide their own templates:

```bash
# Install package with custom template
composer require vendor/easymodules-template-microservice

# Package publishes: config/easymodules-template-microservice.php
php artisan vendor:publish --provider="Vendor\EasyModulesTemplateMicroservice\ServiceProvider"

# Use the new template
php artisan easymodules:new ApiGateway --template=microservice
```

### **Custom Template Creation**

Create your own templates by adding configuration files:

```bash
# Create custom template file
cp config/easymodules-template-mvc.php config/easymodules-template-custom.php

# Edit the configuration
# Set name, label, folders_to_generate, paths, stubs

# Use your custom template
php artisan easymodules:new Project --template=custom
```

---

## 🔄 Migration & Compatibility

### **Backward Compatibility**

Existing modules and configuration remain fully functional:

```php
// config/easymodules.php - continues to work as default
return [
    'folders_to_generate' => [...],  // Used when template = ''
    'paths' => [...],                // Used when template = ''
    'stubs' => [...],                // Used when template = ''
];
```

### **Module Template Detection**

```bash
# Check which template a module uses
php artisan easymodules:info Blog

# Example output:
# 📁 Module: Blog
# 📐 Template: mvc (Traditional Model-View-Controller Pattern)
# 📍 Config: config/easymodules-template-mvc.php
```

### **Template Migration**

```bash
# Change module template (future command)
php artisan easymodules:migrate-template Blog ddd --dry-run

# Check what would change
php artisan easymodules:template-diff Blog ddd
```

---

## 💡 Template Development Guidelines

### **Template Structure Requirements**

Each template file must include:

```php
return [
    'name' => 'TemplateName',           // Required: Short identifier
    'label' => 'Human Readable Label', // Required: Description
    'folders_to_generate' => [...],    // Required: Folder structure
    'paths' => [...],                  // Required: Component paths
    'stubs' => [...],                  // Optional: Custom stubs
];
```

### **Best Practices**

1. **Clear Naming**: Use descriptive names and labels
2. **Complete Paths**: Define paths for all supported components
3. **Custom Stubs**: Provide stubs that match your architecture
4. **Documentation**: Include comments explaining the architecture choice
5. **Validation**: Ensure paths and stubs are consistent

### **Testing Templates**

```bash
# Test template creation
php artisan easymodules:new TestModule --template=yourtemplate

# Verify structure
php artisan easymodules:info TestModule

# Test component generation
php artisan easymodules:make-controller TestModule TestController
```

---

## 🎯 Benefits of Template System

### ✅ **Architecture Flexibility**

-   Support any architectural pattern
-   Per-module template selection
-   No vendor lock-in to specific patterns

### ✅ **Team Collaboration**

-   Different modules can use different patterns
-   Gradual migration between architectures
-   Respect existing team conventions

### ✅ **Extensibility**

-   Third-party templates via packages
-   Custom templates for specific needs
-   Community-driven template ecosystem

### ✅ **Learning & Evolution**

-   Start with familiar patterns (Laravel, MVC)
-   Gradually adopt advanced patterns (DDD, Event Sourcing)
-   Experiment with new architectures safely

---

## 🚀 Future Enhancements

### **Enhanced Command Experience**

-   **Interactive template selection**: Choose template during module creation
-   **Template validation**: Verify template completeness and consistency
-   **Multi-choice stub generation**: Multiple stub variants per component type

### **Advanced Template Features (v2.0+)**

-   **Sub-template system**: Reusable template components to avoid duplication
-   **Template composition**: Combine multiple sub-templates for complex architectures
-   **Template inheritance**: Base templates with extensions and overrides

### **Template Inheritance Example**

```php
// config/easymodules-template-laravel-base.php
return [
    'paths' => [
        'controller' => 'Controllers',
        'model' => 'Models',
        'request' => 'Requests',
        'view' => 'view',
        'component' => 'view/component',
    ],
];

// config/easymodules-template-api.php
return [
    'name' => 'API',
    'includes' => ['laravel-base', 'testing'], // Compose from template
    'paths' => [
        'view' => null,                     // Override to null = removal
        'component' => null,                // Override to null = removal
        'api-controller' => 'Api/Controllers', // Add specific paths
    ],
];

// config/easymodules-template-microservice.php
return [
    'name' => 'Microservice',
    'includes' => ['ddd-base'],
    'paths' => [
        'migration' => null,              // Override to null = removal
        'factory' => null,               // Override to null = removal
        'seeder' => null,                // Override to null = removal
        'view' => null,                  // Override to null = removal
        'http-client' => 'Infrastructure/Http',
        'message-handler' => 'Services/MessageHandlers',
    ],
];

```

The same inheritance logic applies to all template sections: **folders_to_generate**, **stubs**, **scaffold**, **test_paths**, and any other configuration arrays can be inherited, overridden, or extended using the same pattern.

---

## 📊 Template Comparison

| Template               | Complexity | Learning Curve | Use Cases                                  |
| ---------------------- | ---------- | -------------- | ------------------------------------------ |
| **Laravel**            | Very Low   | None           | Laravel familiarity, quick start           |
| **MVC**                | Low        | Easy           | Prototypes, simple apps, Laravel beginners |
| **Clean Architecture** | Medium     | Moderate       | Business applications, team projects       |
| **DDD**                | High       | Advanced       | Complex domains, enterprise applications   |
| **Event Sourcing**     | Very High  | Expert         | High-volume, audit requirements, CQRS      |
| **Custom Business**    | Medium     | Moderate       | Business applications, CRM, integrations   |

---

## 💭 Philosophy

The template system maintains Laravel Easy Modules' core principle: **provide powerful tools without imposing specific architectures**. Whether you're building a simple CRUD app with MVC or a complex enterprise system with Event Sourcing, Laravel Easy Modules adapts to your needs.

**The architecture choice is always yours.** The template system simply makes it easier to implement your chosen pattern consistently across modules.

---

> **🚧 Implementation Status**: This feature is planned for v2.0 of Laravel Easy Modules. The current v1.0 ships with Clean Architecture as the default pattern.

**Want to contribute to this feature?** Check our [Contributing Guide](CONTRIBUTING.md) and join the discussion in [GitHub Issues](https://github.com/kadevland/laravel-easy-modules/issues).

---

**Laravel Easy Modules - Because every architecture deserves great tooling.** 🎯
