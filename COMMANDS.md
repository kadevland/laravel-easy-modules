# EasyModules Command Guide

> **Complete reference for all EasyModules commands with Clean Architecture patterns**

## ⚠️ Important Notice - Development Preview

> **Testing Status**: This package is currently in active development. While the core functionality works, some Laravel commands may not behave exactly as expected.

### Known Considerations

- **Interactive prompts** in Laravel commands (like model/factory creation) may not work perfectly in modular context
- **File placement** might occasionally differ from standard Laravel behavior  
- **Command options** should work, but edge cases haven't been fully tested
- **Path resolution** is functional but may need refinement in complex scenarios

### Recommendation

Test commands in a development environment first, especially:
- `make-model` with `--factory`, `--migration`, `--controller` options
- `make-controller` with `--model`, `--resource`, `--requests` options  
- Commands that generate multiple related files

### Feedback Welcome

If you encounter issues, please [report them on GitHub](https://github.com/kadevland/laravel-easy-modules/issues) to help improve the package!

---


## 📋 Table of Contents

- [🔧 Utility Commands](#-utility-commands)
- [🏗️ EasyModules Specific Commands](#️-easymodules-specific-commands)
- [🚀 Laravel Commands for Modules](#-laravel-commands-for-modules)
- [📊 Command Summary](#-command-summary)

---

## 🔧 Utility Commands

Essential commands for managing your modular Laravel application.

### Create New Modules

```bash
# Create a single module
php artisan easymodules:new Blog

# Create multiple modules at once
php artisan easymodules:new Blog User Product Shop

# Available aliases
php artisan emodules:new Blog
php artisan emodule:new Blog
```

### Publish Configuration & Stubs

```bash
# Publish configuration only (default)
php artisan easymodules:publish

# Publish everything (config + stubs)
php artisan easymodules:publish --all

# Publish stubs only
php artisan easymodules:publish --stubs

# Force overwrite existing files
php artisan easymodules:publish --force

# Available aliases
php artisan emodules:publish --all
php artisan emodule:publish --stubs
```

### List Discovered Modules

```bash
# List all discovered modules
php artisan easymodules:list

# List modules with routes information
php artisan easymodules:list --routes

# Available aliases
php artisan emodules:list --routes
php artisan emodule:list
```

### Flexible Component Generation

```bash
# Generate any component type using configured stubs
php artisan easymodules:make-stub Blog UserEntity entity
php artisan easymodules:make-stub Shop OrderService service
php artisan easymodules:make-stub Auth LoginController controller

# List all available stub types
php artisan easymodules:make-stub --list

# Available aliases
php artisan emodules:make-stub Blog PostEntity entity
php artisan emodule:stub Shop CartService service
```

---

## 🏗️ EasyModules Specific Commands

Commands designed specifically for Clean Architecture patterns.

### 🧠 Domain Layer

#### Domain Entities
```bash
# Create domain entity
php artisan easymodules:make-entity Blog Post
php artisan easymodules:make-entity Shop Product
php artisan easymodules:make-entity User User

# Available aliases
php artisan emodules:make-entity Blog Category
php artisan emodule:entity User Profile
```

#### Domain Services
```bash
# Create domain service
php artisan easymodules:make-service Blog PostDomainService
php artisan easymodules:make-service Shop OrderDomainService
php artisan easymodules:make-service User UserDomainService

# Available aliases
php artisan emodules:make-service Blog CategoryService
php artisan emodule:service User AuthService
```

### ⚙️ Application Layer

#### Application Services
```bash
# Create application service
php artisan easymodules:make-service Blog PostService
php artisan easymodules:make-service Shop CartService
php artisan easymodules:make-service User AuthenticationService

# Available aliases
php artisan emodules:make-service Blog CategoryService
php artisan emodule:service Auth TokenService
```

### 🛠️ Custom Architecture Components

#### Repositories (Clean Architecture)
```bash
# Create repository using flexible generation
php artisan easymodules:make-stub Blog UserRepository repository
php artisan easymodules:make-stub Shop ProductRepository repository
php artisan easymodules:make-stub Auth TokenRepository repository

# Available aliases
php artisan emodules:make-stub Blog PostRepository repository
php artisan emodule:repository User ProfileRepository
```

#### DTOs (Data Transfer Objects)
```bash
# Create DTO using flexible generation
php artisan easymodules:make-stub Blog CreatePostDTO dto
php artisan easymodules:make-stub Shop OrderDTO dto
php artisan easymodules:make-stub User RegisterDTO dto

# Available aliases
php artisan emodules:make-stub Blog PostDTO dto
php artisan emodule:dto User ProfileDTO
```

#### Value Objects
```bash
# Create value object using flexible generation
php artisan easymodules:make-stub Blog PostSlug valueobject
php artisan easymodules:make-stub Shop Money valueobject
php artisan easymodules:make-stub User Email valueobject

# Available aliases
php artisan emodules:make-stub Blog Title valueobject
php artisan emodule:valueobject User Password
```

---

## 🚀 Laravel Commands for Modules

Laravel's built-in commands adapted for modular architecture.

### 🏛️ Infrastructure Layer

#### Models & Database
```bash
# Create Eloquent model
php artisan easymodules:make-model Blog Post
php artisan easymodules:make-model User User --migration

# Model with everything
php artisan easymodules:make-model Blog Post --all

# Model with specific components
php artisan easymodules:make-model Blog Post --controller --resource
php artisan easymodules:make-model Shop Product --factory --seeder
php artisan easymodules:make-model User User --policy --observer

# Pivot model
php artisan easymodules:make-model Blog PostTag --pivot

# Available aliases
php artisan emodules:make-model Blog Category
php artisan emodule:model User Profile
```

#### Migrations
```bash
# Create migration
php artisan easymodules:make-migration Blog create_posts_table
php artisan easymodules:make-migration User add_avatar_to_users_table

# Migration with table creation
php artisan easymodules:make-migration Blog create_posts_table --create=posts

# Migration with table modification
php artisan easymodules:make-migration Blog add_status_to_posts --table=posts

# Custom path migration
php artisan easymodules:make-migration Blog create_posts_table --path=custom/migrations

# Available aliases
php artisan emodules:make-migration Shop create_products_table
php artisan emodule:migration Auth create_tokens_table
```

#### Factories & Seeders
```bash
# Create model factory
php artisan easymodules:make-factory Blog PostFactory
php artisan easymodules:make-factory User UserFactory --model=User

# Create database seeder
php artisan easymodules:make-seeder Blog PostSeeder
php artisan easymodules:make-seeder User UserSeeder

# Available aliases
php artisan emodules:make-factory Shop ProductFactory
php artisan emodule:factory Auth TokenFactory
php artisan emodules:make-seeder Shop ProductSeeder
php artisan emodule:seeder Auth RoleSeeder
```

#### Jobs & Queues
```bash
# Create job
php artisan easymodules:make-job Blog ProcessPost
php artisan easymodules:make-job User SendWelcomeEmail

# Synchronous job
php artisan easymodules:make-job Blog GenerateReport --sync

# Job middleware
php artisan easymodules:make-job-middleware Blog RateLimitPosts
php artisan easymodules:make-job-middleware User ThrottleEmails

# Available aliases
php artisan emodules:make-job Shop ProcessOrder
php artisan emodule:job Auth CleanupTokens
php artisan emodules:make-job-middleware Shop OrderThrottle
php artisan emodule:job-middleware Auth TokenThrottle
```

#### Events & Listeners
```bash
# Create event
php artisan easymodules:make-event Blog PostCreated
php artisan easymodules:make-event User UserRegistered

# Create listener
php artisan easymodules:make-listener Blog SendPostNotification
php artisan easymodules:make-listener User SendWelcomeEmail --event=UserRegistered

# Queued listener
php artisan easymodules:make-listener Blog ProcessPostViews --queued

# Available aliases
php artisan emodules:make-event Shop OrderPlaced
php artisan emodule:event Auth UserLoggedIn
php artisan emodules:make-listener Shop ProcessOrder
php artisan emodule:listener Auth LogUserActivity
```

#### Mail & Notifications
```bash
# Create mailable
php artisan easymodules:make-mail Blog PostPublished
php artisan easymodules:make-mail User WelcomeEmail --markdown=emails.welcome

# Create notification
php artisan easymodules:make-notification Blog PostLiked
php artisan easymodules:make-notification User AccountActivated --markdown=notifications.account

# Available aliases
php artisan emodules:make-mail Shop OrderConfirmation
php artisan emodule:mail Auth PasswordReset
php artisan emodules:make-notification Shop OrderShipped
php artisan emodule:notification Auth SecurityAlert
```

#### Observers & Policies
```bash
# Create observer
php artisan easymodules:make-observer Blog PostObserver
php artisan easymodules:make-observer User UserObserver --model=User

# Create policy
php artisan easymodules:make-policy Blog PostPolicy
php artisan easymodules:make-policy User UserPolicy --model=User

# Available aliases
php artisan emodules:make-observer Shop ProductObserver
php artisan emodule:observer Auth UserObserver
php artisan emodules:make-policy Shop ProductPolicy
php artisan emodule:policy Auth UserPolicy
```

#### Rules & Casts
```bash
# Create validation rule
php artisan easymodules:make-rule Blog ValidSlug
php artisan easymodules:make-rule User UniqueEmail --implicit

# Create custom cast
php artisan easymodules:make-cast Blog PostStatus
php artisan easymodules:make-cast User EncryptedData --inbound

# Available aliases
php artisan emodules:make-rule Shop ValidPrice
php artisan emodule:rule Auth StrongPassword
php artisan emodules:make-cast Shop Money
php artisan emodule:cast Auth HashedPassword
```

#### Exceptions & Channels
```bash
# Create custom exception
php artisan easymodules:make-exception Blog PostNotFoundException
php artisan easymodules:make-exception User InvalidCredentialsException --render

# Create broadcasting channel
php artisan easymodules:make-channel Blog PostChannel
php artisan easymodules:make-channel User UserChannel

# Available aliases
php artisan emodules:make-exception Shop PaymentFailedException
php artisan emodule:exception Auth TokenExpiredException
php artisan emodules:make-channel Shop OrderChannel
php artisan emodule:channel Auth PrivateChannel
```

#### Service Providers
```bash
# Create service provider
php artisan easymodules:make-provider Blog BlogServiceProvider
php artisan easymodules:make-provider User UserServiceProvider

# Available aliases
php artisan emodules:make-provider Shop ShopServiceProvider
php artisan emodule:provider Auth AuthServiceProvider
```

### 🎨 Presentation Layer

#### Controllers
```bash
# Create basic controller
php artisan easymodules:make-controller Blog PostController
php artisan easymodules:make-controller User UserController

# Resource controller
php artisan easymodules:make-controller Blog PostController --resource

# API controller
php artisan easymodules:make-controller Blog PostController --api

# Controller with model
php artisan easymodules:make-controller Blog PostController --model=Post

# Controller with form requests
php artisan easymodules:make-controller Blog PostController --requests

# Invokable controller
php artisan easymodules:make-controller Blog ShowDashboard --invokable

# Nested resource controller
php artisan easymodules:make-controller Blog CommentController --parent=Post

# Singleton controller
php artisan easymodules:make-controller User ProfileController --singleton

# Controller with tests
php artisan easymodules:make-controller Blog PostController --test

# Available aliases
php artisan emodules:make-controller Shop ProductController
php artisan emodule:controller Auth LoginController
```

#### Requests & Resources
```bash
# Create form request
php artisan easymodules:make-request Blog StorePostRequest
php artisan easymodules:make-request Blog UpdatePostRequest

# Create API resource
php artisan easymodules:make-resource Blog PostResource
php artisan easymodules:make-resource User UserResource --collection

# Available aliases
php artisan emodules:make-request Shop CreateOrderRequest
php artisan emodule:request User RegisterRequest
php artisan emodules:make-resource Shop ProductResource
php artisan emodule:resource Auth UserResource
```

#### Middleware & Commands
```bash
# Create middleware
php artisan easymodules:make-middleware Blog CheckPostOwner
php artisan easymodules:make-middleware User EnsureEmailVerified

# Create Artisan command
php artisan easymodules:make-command Blog PublishScheduledPosts
php artisan easymodules:make-command User CleanupInactiveUsers --command=users:cleanup

# Available aliases
php artisan emodules:make-middleware Shop ValidateCart
php artisan emodule:middleware Auth RequireSubscription
php artisan emodules:make-command Shop ProcessOrders
php artisan emodule:command Auth CleanupTokens
```

#### Components
```bash
# Create Blade component
php artisan easymodules:make-component Blog PostCard
php artisan easymodules:make-component User UserProfile

# Inline component
php artisan easymodules:make-component Blog Alert --inline

# Component with custom view
php artisan easymodules:make-component Blog PostList --view=components.post-list

# Available aliases
php artisan emodules:make-component Shop ProductCard
php artisan emodule:component Auth LoginForm
```

### 🧪 Testing

#### Test Classes
```bash
# Create unit test
php artisan easymodules:make-test Blog PostTest --unit
php artisan easymodules:make-test User UserTest --unit

# Create feature test
php artisan easymodules:make-test Blog PostControllerTest
php artisan easymodules:make-test User AuthenticationTest

# Test with custom path
php artisan easymodules:make-test Blog PostServiceTest --path=services --unit

# Pest test
php artisan easymodules:make-test Blog PostTest --pest

# Available aliases
php artisan emodules:make-test Shop ProductTest
php artisan emodule:test Auth LoginTest
```

---

## 📊 Command Summary

### 🔧 Utility Commands (4)
- `easymodules:new` - Create new modules
- `easymodules:publish` - Publish config & stubs  
- `easymodules:list` - List discovered modules
- `easymodules:make-stub` - Flexible component generation

### 🏗️ EasyModules Specific (6)
- `make-entity` - Domain entities
- `make-service` - Application/Domain services
- Plus flexible generation for:
  - Repository patterns
  - DTOs (Data Transfer Objects)
  - Value Objects
  - Custom architecture components

### 🔄 Laravel Commands for Modules (22)
- `make-model` - Eloquent models
- `make-controller` - HTTP controllers
- `make-request` - Form requests
- `make-resource` - API resources
- `make-middleware` - HTTP middleware
- `make-command` - Artisan commands
- `make-component` - Blade components
- `make-migration` - Database migrations
- `make-factory` - Model factories
- `make-seeder` - Database seeders
- `make-job` - Queue jobs
- `make-job-middleware` - Job middleware
- `make-event` - Application events
- `make-listener` - Event listeners
- `make-mail` - Mailable classes
- `make-notification` - Notifications
- `make-observer` - Model observers
- `make-policy` - Authorization policies
- `make-rule` - Validation rules
- `make-cast` - Custom casts
- `make-exception` - Custom exceptions
- `make-channel` - Broadcasting channels
- `make-provider` - Service providers
- `make-test` - Test classes

### 🎯 Total: 32 Commands

**✅ Complete Laravel coverage** + **Clean Architecture patterns** + **Utility commands**

---

## 🔄 Command Aliases

All commands support these prefixes:
- `easymodules:` (full)
- `emodules:` (short)  
- `emodule:` (shortest)

**Example:**
```bash
php artisan easymodules:make-controller Blog PostController
php artisan emodules:make-controller Blog PostController  
php artisan emodule:controller Blog PostController
```

---

## 🏗️ Clean Architecture Benefits

### ✅ **Organized Structure**
- Clear separation of concerns
- Scalable application architecture
- Easy to test and maintain

### ✅ **Laravel Integration**
- All Laravel features preserved
- Seamless development experience
- Auto-discovery and registration

### ✅ **Developer Productivity**
- Quick component generation
- Consistent folder structure
- Intelligent path resolution

---

> **Ready to build scalable Laravel applications with EasyModules ?**  
> Start with `php artisan easymodules:new MyFirstModule` 🚀
