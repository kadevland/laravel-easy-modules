# Architecture Templates - Future Feature Guide

> **🚧 Future Feature**: This document outlines the planned template management system for Laravel Easy Modules, allowing multiple architecture patterns per module.

## 🎯 Overview

The template management system will allow developers to choose between different architecture patterns when creating modules, making EasyModules truly universal for any Laravel project structure.

## 🏗️ Concept

Instead of being limited to one architecture pattern, each module will be able to specify its own architecture template, with intelligent fallback to global defaults.

## 📋 Available Templates

### 🏛️ Clean Architecture (Default)
```
Base: app/Modules/
Structure: Domain/Entities, Application/Services, Infrastructure/Models, Presentation/Controllers
```

### 🔄 MVC Traditional  
```
Base: app/Components/
Structure: Controllers, Models, Views, Services, Helpers
```

### 🎯 Domain Driven Design (DDD)
```
Base: app/BoundedContexts/
Structure: Domain/Aggregates, Application/Commands, Infrastructure/Persistence
```

### ⚙️ Laravel Standard
```
Base: app/Modules/
Structure: Controllers, Models, Requests, Resources, Jobs
```

### 🛠️ Custom Pattern
```
Base: configurable
Structure: Your own folder structure and organization
```

## 🚀 Usage Examples

### Creating Modules with Templates

```bash
# Create module with specific template
php artisan easymodules:new Blog --template=clean-architecture
php artisan easymodules:new Shop --template=mvc
php artisan easymodules:new OrderManagement --template=ddd

# Create with default template (from config)
php artisan easymodules:new Legacy
```

### Module-Specific Configuration

Each module can specify its architecture pattern in its config file:

```php
// app/Modules/Blog/config/config.php (Clean Architecture)
return [
    'name' => 'Blog',
    'enabled' => true,
    'architecture_pattern' => 'clean-architecture',
    
    // Module-specific settings
    'posts_per_page' => 10,
    'cache_ttl' => 3600,
];

// app/BoundedContexts/OrderManagement/config/config.php (DDD)
return [
    'name' => 'OrderManagement',
    'enabled' => true,
    'architecture_pattern' => 'ddd',
    
    // Context-specific settings
    'max_order_items' => 50,
    'payment_timeout' => 300,
];

// app/Components/Shop/config/config.php (MVC)
return [
    'name' => 'Shop',
    'enabled' => true,
    'architecture_pattern' => 'mvc',
    
    // Component-specific settings
    'items_per_page' => 20,
    'enable_wishlist' => true,
];
```

## ⚙️ Global Configuration

The main EasyModules configuration will support multiple templates:

```php
// config/easymodules.php
return [
    // Default template for new modules
    'default_pattern' => 'clean-architecture',
    
    // Available architecture patterns
    'available_patterns' => [
        'clean-architecture',
        'mvc',
        'ddd', 
        'laravel-standard',
        'custom'
    ],
    
    // Template definitions
    'patterns' => [
        'clean-architecture' => [
            'base_path' => app_path('Modules'),
            'base_namespace' => 'App\\Modules',
            'folders_to_generate' => [
                'Domain/Entities',
                'Application/Services',
                'Infrastructure/Models',
                'Presentation/Http/Controllers',
                'Tests/Unit',
                'Tests/Feature',
                // ... complete Clean Architecture structure
            ],
            'paths' => [
                'entity' => 'Domain/Entities',
                'service' => 'Application/Services',
                'model' => 'Infrastructure/Models',
                'controller' => 'Presentation/Http/Controllers',
                // ... complete path mapping
            ]
        ],
        
        'mvc' => [
            'base_path' => app_path('Components'),
            'base_namespace' => 'App\\Components',
            'folders_to_generate' => [
                'Controllers',
                'Models', 
                'Views',
                'Services',
                'Helpers',
                'Tests',
                // ... MVC structure
            ],
            'paths' => [
                'controller' => 'Controllers',
                'model' => 'Models',
                'service' => 'Services',
                'view' => 'Views',
                // ... MVC path mapping
            ]
        ],
        
        'ddd' => [
            'base_path' => app_path('BoundedContexts'),
            'base_namespace' => 'App\\BoundedContexts',
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
                // ... DDD structure
            ],
            'paths' => [
                'aggregate' => 'Domain/Aggregates',
                'valueobject' => 'Domain/ValueObjects',
                'event' => 'Domain/Events', 
                'command' => 'Application/Commands',
                'query' => 'Application/Queries',
                'handler' => 'Application/Handlers',
                'repository' => 'Infrastructure/Persistence',
                // ... DDD path mapping
            ]
        ]
    ]
];
```

## 🧠 Intelligent Command Resolution

Commands will automatically detect the module's template and adapt their behavior:

```php
// Example: Creating a controller in different templates

// Clean Architecture module
php artisan easymodules:make-controller Blog PostController
// → Creates: app/Modules/Blog/Presentation/Http/Controllers/PostController.php

// MVC module  
php artisan easymodules:make-controller Shop ProductController
// → Creates: app/Components/Shop/Controllers/ProductController.php

// DDD module
php artisan easymodules:make-command OrderManagement ProcessOrderCommand  
// → Creates: app/BoundedContexts/OrderManagement/Application/Commands/ProcessOrderCommand.php
```

## 📊 Module Information with Templates

The `easymodules:info` command will show template information:

```bash
php artisan easymodules:info Blog
```

**Example output:**
```

📁 Module: Blog
📐 Architecture Pattern: clean-architecture
📍 Base Path: app/Modules/Blog
🔧 Namespace: App\Modules\Blog

🏗️ Scaffold Structure:
  ✅ Providers/ 
  ✅ config/ 
  ✅ routes/

📁 Generated Structure:
  ✅ Application/Services 
  ✅ Domain/Entities 
  ✅ Infrastructure/Models
  ❌ Tests/Feature 
  ❌ Infrastructure/Exceptions 
  ❌ lang/

📄 Additional Structures (not in config):
  ✅ Custom/Helpers/ 
  ✅ Legacy/Support/ 
  ✅ Docs/

🔧 Registration:
  ✅ ServiceProvider: BlogServiceProvider (registered)
  ✅ Routes: web.php, ✅ api.php ❌ console.php

```bash
php artisan easymodules:info OrderManagement
```

**Example DDD output:**
```
📁 Bounded Context: OrderManagement
📐 Architecture Pattern: ddd
📍 Base Path: app/BoundedContexts/OrderManagement
🔧 Namespace: App\BoundedContexts\OrderManagement

🏗️ Scaffold Structure:
  ✅ Providers/ 
  ✅ config/ 
  ✅ routes/

📁 Generated Structure:
  ✅ Domain/Aggregates 
  ✅ Domain/ValueObjects 
  ✅ Application/Commands 
  ✅ Application/Handlers 
  ✅ Infrastructure/Persistence 
  ❌ Tests/Integration

🔧 Registration:
  ✅ ServiceProvider: BlogServiceProvider (registered)
  ✅ Routes: web.php, ✅ api.php ❌ console.php
```

## 🔄 Template Migration

### Changing Module Template

```php
// Change template in module config
// app/Modules/Blog/config/config.php
return [
    'name' => 'Blog',
    'architecture_pattern' => 'mvc', // Changed from 'clean-architecture'
];
```

### Migration Helper Commands

```bash
# Check what would change
php artisan easymodules:template-diff Blog mvc

# Migrate module structure (future command)
php artisan easymodules:migrate-template Blog mvc --dry-run
```

## 🎯 Template Validation

### Valid Template Check

```bash
# Validate module template
php artisan easymodules:validate-template Blog

# List available templates
php artisan easymodules:list-templates
```

**Example output:**
```
📋 Available Architecture Templates:

🏛️ clean-architecture (default)
   📍 Base: app/Modules/
   Domain-driven with Clean Architecture layers
   
🔄 mvc  
   📍 Base: app/Components/
   Traditional Model-View-Controller pattern
   
🎯 ddd
   📍 Base: app/BoundedContexts/
   Domain Driven Design with bounded contexts
   
⚙️ laravel-standard
   📍 Base: app/Modules/
   Standard Laravel application structure
   
🛠️ custom
   📍 Base: configurable
   Your own custom folder structure
```

## 🔧 Custom Templates

### Creating Custom Templates

```php
// config/easymodules.php
'patterns' => [
    'hexagonal' => [
        'base_path' => app_path('Contexts'),
        'base_namespace' => 'App\\Contexts',
        'folders_to_generate' => [
            'Core/Domain',
            'Core/Application',
            'Adapters/Primary/Web',
            'Adapters/Primary/API',
            'Adapters/Secondary/Database',
            'Adapters/Secondary/External',
            'Tests/Unit',
            'Tests/Integration'
        ],
        'paths' => [
            'entity' => 'Core/Domain',
            'service' => 'Core/Application',
            'controller' => 'Adapters/Primary/Web',
            'api' => 'Adapters/Primary/API',
            'repository' => 'Adapters/Secondary/Database'
        ]
    ]
]
```

### Using Custom Templates

```bash
# Create module with custom template
php artisan easymodules:new PaymentGateway --template=hexagonal

# Generate components using custom paths
php artisan easymodules:make-controller PaymentGateway PaymentController
# → Creates: app/Contexts/PaymentGateway/Adapters/Primary/Web/PaymentController.php
```

## 🚀 Benefits

### ✅ **Universal Compatibility**
- Support any architecture pattern
- Easy migration between patterns
- No vendor lock-in

### ✅ **Team Flexibility** 
- Different modules, different patterns
- Progressive adoption of new architectures
- Respect existing team conventions

### ✅ **Learning Path**
- Start with familiar MVC
- Gradually move to Clean Architecture
- Experiment with DDD when ready

### ✅ **Project Evolution**
- Legacy modules keep their structure
- New modules use modern patterns  
- Smooth architectural transitions

## 📊 Structure Comparison

### Clean Architecture
```
app/Modules/Blog/
├── Domain/Entities/Post.php
├── Application/Services/PostService.php
├── Infrastructure/Models/Post.php
└── Presentation/Http/Controllers/PostController.php
```

### Traditional MVC
```
app/Components/Shop/
├── Controllers/ProductController.php
├── Models/Product.php
├── Services/ProductService.php
└── Views/products/
```

### Domain Driven Design
```
app/BoundedContexts/OrderManagement/
├── Domain/Aggregates/Order.php
├── Application/Commands/CreateOrderCommand.php
├── Application/Handlers/CreateOrderHandler.php
└── Infrastructure/Persistence/OrderRepository.php
```

## 📝 Implementation Status

> **🚧 This is a planned feature for future versions of EasyModules**

**Current Status:** Design phase
**Target:** Post stable release
**Prerequisites:** Complete current command set, comprehensive testing

**Want to contribute to this feature?** Check our [Contributing Guide](CONTRIBUTING.md) and join the discussion in [GitHub Issues](https://github.com/kadevland/laravel-easy-modules/issues).

---

## 💡 Philosophy

This template system maintains Laravel Easy Modules' core principle: **provide powerful tools without imposing specific architectures**. Whether you're building a simple CRUD app with MVC or a complex enterprise system with Clean Architecture, Laravel Easy Modules will adapt to your needs.

**The choice is always yours.** 🎯
