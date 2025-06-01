# Laravel Easy Modules - Flexible Module Generator

[![Latest Version on Packagist](https://img.shields.io/packagist/v/kadevland/laravel-easy-modules.svg?style=flat-square)](https://packagist.org/packages/kadevland/laravel-easy-modules)
[![Total Downloads](https://img.shields.io/packagist/dt/kadevland/laravel-easy-modules.svg?style=flat-square)](https://packagist.org/packages/kadevland/laravel-easy-modules)
[![GitHub Actions](https://github.com/kadevland/laravel-easy-modules/actions/workflows/main.yml/badge.svg)](https://github.com/kadevland/laravel-easy-modules/actions)
[![License](https://img.shields.io/packagist/l/kadevland/laravel-easy-modules.svg?style=flat-square)](https://packagist.org/packages/kadevland/laravel-easy-modules)

> **Package Status**: This package has been tested and works correctly for most use cases. However, some edge cases may require manual handling depending on your specific setup.

**Laravel Easy Modules** is a flexible Laravel package that enables you to organize your application using **modular architecture**. Generate organized, maintainable applications with automatic component registration and structured code separation. **Clean Architecture** templates are provided as sensible defaults, but fully customizable to your needs.

## ✨ Key Features

-   🏗️ **Flexible Module Generation** - Customizable architecture patterns with sensible defaults
-   ⚡ **Extensive Artisan Commands** - Complete toolkit for rapid modular development
-   🔄 **Auto-Discovery** - Automatic module registration and loading
-   🎯 **Fully Customizable** - Adapt any folder structure and architectural pattern
-   🚀 **Developer Friendly** - Simple commands with intelligent defaults
-   🏛️ **Clean Architecture Ready** - Pre-configured templates for Domain, Application, Infrastructure, and Presentation layers
-   🛠️ **Development Toolkit** - Optimized for development workflow with minimal production footprint
-   🆕 **Laravel 12 Ready** - Full compatibility with Laravel 12's latest features

## 🚀 Installation & Quick Start

### Requirements

-   **Laravel 12+** - Built specifically for Laravel 12
-   **PHP 8.2+** - Required by Laravel 12
-   **Composer** - For package management

### Installation

Install via Composer:

```bash
composer require --dev kadevland/laravel-easy-modules
```

Publish configuration:

```bash
# Default way
php artisan vendor:publish --provider="Kadevland\EasyModules\EasyModulesServiceProvider" --tag="config"

# Using Laravel Easy Modules command
php artisan easymodules:publish

# Publish with options
php artisan easymodules:publish --all      # Config & stubs
php artisan easymodules:publish --stubs    # Stubs only
php artisan easymodules:publish --force    # Force overwrite
```

Create your first module:

```bash
# Single module
php artisan easymodules:new Blog

# Multiple modules
php artisan easymodules:new Blog User Product

# Using aliases
php artisan emodules:new Shop
php artisan emodule:new Auth
```

## 📁 What Gets Generated

When you run `php artisan easymodules:new Blog`, you get a complete Clean Architecture structure by default, but **this is fully customizable** to match your preferred architecture pattern:

> 📝 **Note**: This structure is just the default template. You can completely customize the folder structure, paths, and architectural patterns through configuration. See [Configuration Guide](CONFIGURATION.md) for details.

```
app/Modules/Blog/
├── 📁 Application/              # 🎯 Use Cases & Business Logic
│   ├── Actions/                 # Use case implementations
│   ├── DTOs/                    # Data Transfer Objects
│   ├── Interfaces/              # Contracts and interfaces
│   ├── Mappers/                 # Data transformation logic
│   ├── Rules/                   # Business rules validation
│   ├── Services/                # Application services
│   └── Validation/              # Input validation logic
├── 📁 Domain/                   # 🧠 Core Business Logic
│   ├── Entities/                # Domain entities (business models)
│   ├── Services/                # Domain services (business logic)
│   └── ValueObjects/            # Value objects used in entities
├── 📁 Infrastructure/           # 🏛️ External Concerns
│   ├── Casts/                   # Custom Eloquent casts
│   ├── Events/                  # Application events
│   ├── Exceptions/              # Custom exceptions for error handling
│   ├── Jobs/                    # Queue jobs and background tasks
│   ├── Listeners/               # Event listeners
│   ├── Mail/                    # Mailable classes
│   ├── Mappers/                 # Entity ↔ Model transformation
│   ├── Models/                  # Eloquent models (database persistence)
│   ├── Notifications/           # Notification classes
│   ├── Observers/               # Model observers
│   ├── Persistences/            # Repositories and data access
│   ├── Policies/                # Authorization policies
│   ├── Rules/                   # Validation rules
│   └── Services/                # External services integration
├── 📁 Presentation/             # 🎨 User Interface
│   ├── Console/Commands/        # Custom Artisan commands
│   ├── Http/Controllers/        # HTTP controllers for request handling
│   ├── Http/Middlewares/        # HTTP middleware
│   ├── Http/Requests/           # Form requests for validation
│   ├── Http/Resources/          # API resources for response formatting
│   ├── Mappers/                 # Display-related transformations
│   ├── Views/Components/        # Blade components for UI
│   └── resources/views/         # Blade templates
├── 📁 Database/                 # 🗄️ Database Related
│   ├── Factories/               # Model factories for testing
│   ├── Migrations/              # Database schema management
│   └── Seeders/                 # Database seeders
├── 📁 Tests/                    # 🧪 Testing
│   ├── Feature/                 # Integration/Feature tests
│   └── Unit/                    # Unit testing
├── 📁 Providers/                # 🔧 Service Providers
│   └── BlogServiceProvider.php # Auto-generated and registered
├── 📁 config/                   # ⚙️ Configuration
│   └── config.php               # Module-specific configuration
├── 📁 routes/                   # 🛣️ Route Definitions
│   ├── web.php                  # Web routes (with examples)
│   ├── api.php                  # API routes (with examples)
│   └── console.php              # Console routes (with examples)
└── 📁 lang/                     # 🌍 Translations    
```

### 🏗️ Modular Architecture Benefits

-   🎯 **Separation of Concerns**: Each layer has specific responsibilities
-   🔄 **Testability**: Easy to unit test business logic in isolation
-   📈 **Scalability**: Add features without affecting existing code
-   🔧 **Maintainability**: Clear structure for team collaboration
-   🏆 **Independence**: Domain logic independent of frameworks and databases

## 🛠️ Commands & Generators

Laravel Easy Modules provides an extensive command toolkit for rapid development:

**👉 [Complete Command Reference Guide](COMMANDS.md)** - Full documentation with examples

### Quick Examples

```bash
# Create a complete blog module
php artisan easymodules:new Blog

# Generate domain components
php artisan easymodules:make-entity Blog Post

# Use familiar Laravel commands in modules
php artisan easymodules:make-model Blog Post --migration --factory

# Flexible component generation with custom stubs
php artisan easymodules:make-stub Blog UserRepository repository
php artisan easymodules:make-stub Shop OrderDTO dto
php artisan easymodules:make-stub User EmailValueObject valueobject

# Get detailed module information
php artisan easymodules:info Blog

# List discovered modules
php artisan easymodules:list --routes
```

### Command Aliases

All commands support these prefixes for convenience:
- `easymodules:` (full)
- `emodules:` (short)
- `emodule:` (shortest)

## 🔄 Laravel 12 Auto-Discovery

Laravel Easy Modules leverages Laravel 12's enhanced auto-discovery features for seamless integration:

### ✅ Automatic Registration

When `auto_discover = true`, newly created modules are automatically:

-   **Registered** in `bootstrap/providers.php` using Laravel's official method
-   **Loaded** on application startup
-   **Available** immediately without manual configuration

```php
// bootstrap/providers.php (automatically updated)
return [
    App\Providers\AppServiceProvider::class,
    App\Modules\Blog\Providers\BlogServiceProvider::class, // ← Auto-added
];
```

### 🔧 Manual Registration

You can also register modules manually by adding them directly to `bootstrap/providers.php`:

```php
// bootstrap/providers.php
return [
    App\Providers\AppServiceProvider::class,
    App\Modules\Blog\Providers\BlogServiceProvider::class,
    App\Modules\User\Providers\UserServiceProvider::class,
    // Add your modules here
];
```

### 🗑️ Manual Unregistration

To disable a module, simply remove or comment its ServiceProvider from `bootstrap/providers.php`:

```php
// bootstrap/providers.php
return [
    App\Providers\AppServiceProvider::class,
    // App\Modules\Blog\Providers\BlogServiceProvider::class, // ← Disabled
    App\Modules\User\Providers\UserServiceProvider::class,
];
```

### 🏗️ Package Independence

**Important**: All generated code remains fully functional even if you remove Laravel Easy Modules package. Each module generated uses standard Laravel ServiceProvider patterns and can operate independently.

### 🔍 List Discovered Modules

View all modules discovered by the auto-discovery system:

```bash
# View all discovered modules with detailed information
php artisan easymodules:list --routes
```

**Example output:**

```
📋 Laravel Easy Modules - Module Discovery

📁 Base Path: /app/Modules
📦 Base Namespace: App\Modules
🔍 Auto-Discovery: ✅ Enabled

+---------+------------------+---------------------+-----+-----+---------+
| Module  | Path             | Provider            | Web | API | Console |
+---------+------------------+---------------------+-----+-----+---------+
| Blog    | /app/Modules/Blog| BlogServiceProvider | ✅  | ✅  | ❌      |
| User    | /app/Modules/User| UserServiceProvider | ✅  | ❌  | ❌      |
| Shop    | /app/Modules/Shop| ShopServiceProvider | ✅  | ✅  | ✅      |
+---------+------------------+---------------------+-----+-----+---------+

📊 Summary:
   Total modules: 3
   With web routes: 3
   With API routes: 2
   With console routes: 1
```

## ⚙️ Configuration & Customization

### Package Configuration

Customize module generation in `config/easymodules.php`:

```php
return [
    // Module location and namespace
    'base_path' => app_path('Modules'),
    'base_namespace' => 'App\\Modules',

    // Laravel 12 auto-discovery
    'auto_discover' => true,

    // Custom paths per component
    'paths' => [
        'controller' => 'Presentation/Http/Controllers',
        'model' => 'Infrastructure/Models',
        'entity' => 'Domain/Entities',
        // ... fully customizable
    ],

    // Custom stubs for flexible architecture
    'stubs' => [
        'controller' => 'easymodules/controller.stub',
        'entity' => 'easymodules/entity.stub',
        'dto' => 'easymodules/dto.stub',
        'repository' => 'easymodules/repository.stub',
        // ... your custom templates
    ]
];
```

### Module-Specific Configuration

Each module can have its own configuration for module-specific settings:

```php
// app/Modules/Blog/config/config.php
return [
    'name' => 'Blog',
    'enabled' => true,

    // Your custom settings
    'posts_per_page' => 10,
    'cache_ttl' => 3600,
    'features' => [
        'comments' => true,
        'categories' => true,
        'seo' => true,
    ],
    'seo' => [
        'meta_length' => 160,
        'slug_separator' => '-',
    ],
];

// Access in your code
config('blog.posts_per_page'); // 10
config('blog.features.comments'); // true
```

### Customizable Stubs

```bash
# Publish stubs for customization
php artisan easymodules:publish --stubs

# Modify in resources/stubs/easymodules/
# Create your own architectural patterns
```

**Important**: No stubs are provided by default - you create them according to your architectural needs using the `make-stub` system.

## 🧪 Testing Configuration

### PHPUnit Integration
Add to your `phpunit.xml`:

```xml
<testsuites>
    <testsuite name="Feature">
        <directory suffix="Test.php">./tests/Feature</directory>
        <directory suffix="Test.php">./app/Modules/*/Tests/Feature</directory>
    </testsuite>
    <testsuite name="Unit">
        <directory suffix="Test.php">./tests/Unit</directory>
        <directory suffix="Test.php">./app/Modules/*/Tests/Unit</directory>
    </testsuite>
</testsuites>
```

### Pest Framework Support
Add to your `tests/Pest.php`:

```php
uses(Tests\TestCase::class)->in('Feature', 'Unit');
uses(Tests\TestCase::class)->in('app/Modules/*/Tests/Feature');
uses(Tests\TestCase::class)->in('app/Modules/*/Tests/Unit');
```

## ⚡ Vite Integration (Laravel 12)

Laravel 12 uses enhanced Vite configuration. Update your `vite.config.js`:

```js
import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import tailwindcss from "@tailwindcss/vite";
import { glob } from "glob";

// Auto-discovery of module assets
const moduleAssets = [
    ...glob.sync("app/Modules/*/Presentation/resources/js/**/*.js"),
    ...glob.sync("app/Modules/*/Presentation/resources/css/**/*.css"),
    ...glob.sync("app/Modules/*/Presentation/resources/scss/**/*.scss"),
];

export default defineConfig({
    plugins: [
        laravel({
            input: [
                "resources/css/app.css",
                "resources/js/app.js",
                ...moduleAssets, // Auto-discovered module assets
            ],
            refresh: [
                // Default Laravel refresh
                "resources/views/**",
                "routes/**",
                "app/**",
                // Module-specific refresh
                "app/Modules/*/Presentation/resources/views/**",
                "app/Modules/*/Presentation/Views/Components/**",
                "app/Modules/*/routes/**",
            ],
        }),
        tailwindcss(), // Laravel 12 Tailwind plugin
    ],
    resolve: {
        alias: {
            "@": "/resources/js",
            "@modules": "/app/Modules",
        },
    },
});
```

**Required installation:**

```bash
npm install glob --save-dev
```

### 🎨 Tailwind CSS Configuration

Update your `tailwind.config.js` for module support:

```js
export default {
    content: [
        "./resources/**/*.blade.php",
        "./resources/**/*.js",
        "./resources/**/*.vue",
        "./app/**/*.php",

        // Laravel Easy Modules with Clean Architecture
        "./app/Modules/*/Presentation/resources/views/**/*.blade.php",
        "./app/Modules/*/Presentation/resources/**/*.js",
        "./app/Modules/*/Presentation/resources/**/*.vue",
        "./app/Modules/*/Presentation/Views/Components/**/*.php",
    ],
    theme: {
        extend: {},
    },
    plugins: [],
};
```

## 📖 Practical Examples

### Blog Module with Rich Configuration

```bash
php artisan easymodules:new Blog
```

```php
// app/Modules/Blog/config/config.php
return [
    'name' => 'Blog',
    'enabled' => true,
    'posts_per_page' => 15,
    'cache' => [
        'posts_ttl' => 3600,
        'categories_ttl' => 7200,
    ],
    'features' => [
        'comments' => true,
        'tags' => true,
        'seo' => true,
        'social_sharing' => true,
    ],
    'seo' => [
        'meta_description_length' => 160,
        'slug_separator' => '-',
        'auto_generate_meta' => true,
    ],
];
```

### E-commerce Modular Setup

```bash
# Create separate modules for clean domain separation
php artisan easymodules:new Product Order Payment User Cart Inventory
```

Each module maintains its own:
- **Domain logic** in isolated entities
- **Database schema** with dedicated migrations
- **API endpoints** with versioned resources
- **Tests** for reliable functionality

### Multi-tenant Application

```bash
# Tenant-specific modules
php artisan easymodules:new Tenant Organization Billing Subscription
```

## 🆕 Laravel 12 Compatibility

### ✅ What's Fully Supported

-   **ServiceProvider Auto-Registration** - Uses Laravel 12's official `addProviderToBootstrapFile` method
-   **All Essential Laravel Commands** - Full compatibility with Laravel's core Artisan commands within modules
-   **PHP 8.2+** - Takes advantage of modern PHP features and syntax
-   **Enhanced Vite** - Works with Laravel 12's improved asset compilation
-   **Framework Features** - Complete integration with Laravel 12's core functionality

### 🚀 Built for Laravel 12

Laravel Easy Modules is designed specifically for Laravel 12 from the ground up - no migration needed, just clean modular development ready to use.

## 🛠️ Benefits of Modular Architecture

### ✅ **Separation of Concerns**
- **Domain** : Pure business logic, framework-independent
- **Application** : Use cases and orchestration logic
- **Infrastructure** : Persistence, external services, and technical details
- **Presentation** : User interface, APIs, and external communication

### ✅ **Development Benefits**
- **Team Collaboration** : Multiple developers can work on different modules
- **Code Organization** : Logical grouping by business functionality
- **Reusability** : Modules can be extracted as packages
- **Testing** : Isolated testing of business logic

### ✅ **Scalability & Maintenance**
- **Independent Deployment** : Modules can evolve separately
- **Feature Isolation** : New features don't affect existing modules
- **Easier Debugging** : Clear boundaries help identify issues
- **Legacy Migration** : Gradual modernization of existing applications

## 📚 Complete Documentation

- 📖 **[Command Guide](COMMANDS.md)** - Complete reference for all commands
- 🔧 **[Configuration Guide](CONFIGURATION.md)** - Advanced customization and architectural patterns
- 🏗️ **[Architecture Templates](TEMPLATES.md)** - Future multi-pattern feature

## 🔄 Alternative Solutions

If you're looking for modular Laravel development solutions, you might also consider:

### **[nWidart/laravel-modules](https://github.com/nWidart/laravel-modules)**
A well-established and highly configurable module system for Laravel. Great choice if you prefer maximum flexibility and don't mind setting up your own structure from scratch.

**EasyModules vs nWidart:**
- **EasyModules**: Simple setup, structured defaults, future multi-pattern support, production-independent modules. EasyModules is a dev-tool only - no need to deploy it to production
- **nWidart**: Complete flexibility, manual configuration, established ecosystem

Both packages serve the modular development community well - choose based on whether you prefer structured defaults (EasyModules) or complete configurability (nWidart).

## 💭 Philosophy

We created EasyModules because modular development should be simple, fast, and completely independent - remove EasyModules anytime, your code keeps running.

**EasyModules believes in empowering developers without creating dependencies.** We're here to help you design and scaffold beautiful, maintainable modular architectures using Laravel standards - then we disappear. 

**Configuration is simple by design.** A single config file, clear folder structures, and intelligent defaults get you productive in minutes. No complex setup, no scattered configuration files, no learning curve.

**Generated code is entirely yours to modify.** EasyModules doesn't check, validate, or enforce anything after generation. Refactor freely, change structures, adapt to your needs - we're here to help, not to impose.

**Generated modules are entirely yours.** Built with standard Laravel patterns. No vendor lock-in - you choose how to manage your dependencies. Just clean, organized code that lives and breathes Laravel.

*"We help you build it right, then get out of your way."*

## 🤝 Contributing

Contributions are welcome! Please see [contributing guide](CONTRIBUTING.md).

## 📝 Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on recent changes.

## 🔒 Security

For security issues, please email kadevland@kaosland.net.

## 📄 License

Open-source package under [MIT](LICENSE.md) license.

## 👨‍💻 Credits

- **[Kadevland](https://github.com/kadevland)** - Creator and maintainer
- **[Contributors](../../contributors)** - Thank you for your contributions!

---

<div align="center">

**Made with ❤️ by [Kadevland](https://github.com/kadevland)**

[⭐ Star us on GitHub](https://github.com/kadevland/laravel-easy-modules) if this project helps you!

**Laravel Easy Modules - Flexible modular development made simple** 🚀

</div>
