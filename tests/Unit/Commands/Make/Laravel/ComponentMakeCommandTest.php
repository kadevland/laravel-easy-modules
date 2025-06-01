<?php

declare(strict_types=1);

namespace Kadevland\EasyModules\Tests\Unit\Commands\Make\Laravel;

use Kadevland\EasyModules\Tests\Support\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Test suite for ComponentMakeCommand
 *
 * This command extends Laravel's base ComponentMakeCommand to generate
 * view component classes within the modular structure, supporting
 * both regular components and inline components.
 */
class ComponentMakeCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->setComponentPath('component', 'Presentation/Views/Components');

        // Set app environment to testing to disable prompts
        $this->app['config']->set('app.env', 'testing');
    }

    protected function tearDown(): void
    {
        // Enhanced cleanup - remove all test files
        $testPaths = [
            'Blog/Presentation/Views/Components',
            'Shop/Presentation/Views/Components',
            'Test/Presentation/Views/Components',
            'Custom/Domain/Components',
        ];

        foreach ($testPaths as $path) {
            $fullPath = $this->testBasePath($path);
            if ($this->files->isDirectory($fullPath)) {
                $this->files->deleteDirectory($fullPath, true);
            }
        }

        parent::tearDown();
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // NIVEAU 5: BASIC COMPONENT GENERATION TESTS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Test basic component file generation
     */
    #[Test]
    public function it_can_generate_basic_component_file(): void
    {
        $this->runEasyModulesCommand('make-component', 'Blog', 'Alert');

        $this->assertModuleComponentExists('Blog', 'Presentation/Views/Components', 'Alert', [
            'use Illuminate\View\Component;',
            'class Alert extends Component',
            "return view('components.alert');",
        ]);
    }

    /**
     * Test inline component file generation
     */
    #[Test]
    public function it_can_generate_inline_component_file(): void
    {
        $this->runEasyModulesCommand('make-component', 'Blog', 'Button', ['--inline' => true]);

        $this->assertModuleComponentExists('Blog', 'Presentation/Views/Components', 'Button', [
            'use Illuminate\View\Component;',
            'class Button extends Component',
            "return <<<'blade'",
        ]);

        // Should NOT contain view reference for inline components
        $this->assertFileNotContains([
            "return view('components.button');",
        ], 'Blog/Presentation/Views/Components/Button.php');
    }

    /**
     * Test component generation with different naming patterns
     */
    #[Test]
    public function it_can_generate_components_with_different_names(): void
    {
        $componentNames = [
            'Alert',
            'Button',
            'Card',
            'Modal',
        ];

        foreach ($componentNames as $componentName) {
            $this->runEasyModulesCommand('make-component', 'Blog', $componentName);

            $this->assertFilenameExists("Blog/Presentation/Views/Components/{$componentName}.php");
            $this->assertFileContains([
                'namespace App\\Modules\\Blog\\Presentation\\Views\\Components;',
                'use Illuminate\\View\\Component;',
                "class {$componentName} extends Component",
            ], "Blog/Presentation/Views/Components/{$componentName}.php");
        }
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // NIVEAU 4: NAMESPACE CORRECTNESS TESTS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Test component namespace generation is correct
     */
    #[Test]
    public function it_generates_correct_component_namespace(): void
    {
        $this->runEasyModulesCommand('make-component', 'Blog', 'NamespaceTest');

        // Component should have correct namespace
        $this->assertFileContains([
            'namespace App\\Modules\\Blog\\Presentation\\Views\\Components;',
        ], 'Blog/Presentation/Views/Components/NamespaceTest.php');

        // Should NOT contain duplicated namespace segments
        $this->assertFileNotContains([
            'namespace App\\Modules\\Blog\\Presentation\\Views\\Components\\Presentation\\Views\\Components;',
            'namespace App\\Modules\\Blog\\Blog\\Presentation\\Views\\Components;',
        ], 'Blog/Presentation/Views/Components/NamespaceTest.php');
    }

    /**
     * Test component methods are properly generated
     */
    #[Test]
    public function it_generates_correct_component_methods(): void
    {
        $this->runEasyModulesCommand('make-component', 'Blog', 'MethodTest');

        $this->assertFileContains([
            'public function render()',
            "return view('components.method-test');",
        ], 'Blog/Presentation/Views/Components/MethodTest.php');
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // NIVEAU 3: DOUBLE PATH PREVENTION TESTS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Test prevention of double path and namespace duplication
     *
     * This test ensures that neither file paths nor namespaces are duplicated
     * when generating components within the module structure.
     */
    #[Test]
    public function it_prevents_double_path_and_namespace_duplication(): void
    {
        $this->runEasyModulesCommand('make-component', 'Blog', 'PathTest');

        // ✅ File path verification
        $this->assertFilenameExists('Blog/Presentation/Views/Components/PathTest.php');
        $this->assertFilenameNotExists('Blog/Presentation/Views/Components/Presentation/Views/Components/PathTest.php');

        // ✅ Namespace duplication prevention
        $this->assertFileNotContains([
            'App\\Modules\\Blog\\Presentation\\Views\\Components\\Presentation\\Views\\Components',
            'App\\Modules\\Blog\\Blog\\Presentation\\Views\\Components',
            'Presentation\\Views\\Components\\Presentation\\Views\\Components',
        ], 'Blog/Presentation/Views/Components/PathTest.php');
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // NIVEAU 2: CUSTOM CONFIGURATION TESTS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Test component generation with nested names
     */
    #[Test]
    public function it_can_generate_nested_components(): void
    {
        $this->runEasyModulesCommand('make-component', 'Blog', 'Forms/Input');

        // Should create nested directory structure
        $this->assertFilenameExists('Blog/Presentation/Views/Components/Forms/Input.php');

        $this->assertFileContains([
            'namespace App\\Modules\\Blog\\Presentation\\Views\\Components\\Forms;',
            'class Input extends Component',
        ], 'Blog/Presentation/Views/Components/Forms/Input.php');
    }

    /**
     * Test deeply nested component generation
     */
    #[Test]
    public function it_handles_deeply_nested_components(): void
    {
        $this->runEasyModulesCommand('make-component', 'Blog', 'Admin/Forms/Input');

        $this->assertFilenameExists('Blog/Presentation/Views/Components/Admin/Forms/Input.php');

        $this->assertFileContains([
            'namespace App\\Modules\\Blog\\Presentation\\Views\\Components\\Admin\\Forms;',
            'class Input extends Component',
        ], 'Blog/Presentation/Views/Components/Admin/Forms/Input.php');

        // Should not contain path duplication in deeply nested structure
        $this->assertFileNotContains([
            'namespace App\\Modules\\Blog\\Presentation\\Views\\Components\\Admin\\Forms\\Presentation\\Views\\Components\\Admin\\Forms;',
        ], 'Blog/Presentation/Views/Components/Admin/Forms/Input.php');
    }

    /**
     * Test component generation with custom module configuration
     */
    #[Test]
    public function it_handles_custom_module_configurations(): void
    {
        // Test with custom base namespace and paths
        $this->app['config']->set('easymodules.base_namespace', 'Custom\\Modules');
        $this->app['config']->set('easymodules.paths.component', 'Domain/Components');

        $this->runEasyModulesCommand('make-component', 'Shop', 'ProductCard');

        // Check custom paths are used
        $this->assertFilenameExists('Shop/Domain/Components/ProductCard.php');

        // Check custom namespaces
        $this->assertFileContains([
            'namespace Custom\\Modules\\Shop\\Domain\\Components;',
            'class ProductCard extends Component',
        ], 'Shop/Domain/Components/ProductCard.php');

        // Should NOT contain double namespace with custom config
        $this->assertFileNotContains([
            'namespace Custom\\Modules\\Shop\\Domain\\Components\\Domain\\Components;',
        ], 'Shop/Domain/Components/ProductCard.php');
    }

    /**
     * Test component generation with different module structures
     */
    #[Test]
    public function it_handles_different_module_structures(): void
    {
        $testConfigs = [
            ['namespace' => 'App\\Components', 'componentPath' => 'Components'],
            ['namespace' => 'Modules', 'componentPath' => 'View/Components'],
            ['namespace' => 'Custom\\App\\Modules', 'componentPath' => 'Presentation/Views/Components'],
        ];

        foreach ($testConfigs as $index => $config) {
            $this->app['config']->set('easymodules.base_namespace', $config['namespace']);
            $this->app['config']->set('easymodules.paths.component', $config['componentPath']);

            $componentName = "Test{$index}Component";

            $this->runEasyModulesCommand('make-component', 'Test', $componentName);

            $expectedComponentPath = "Test/{$config['componentPath']}/{$componentName}.php";
            $this->assertFilenameExists($expectedComponentPath);

            $expectedComponentNamespace = str_replace('/', '\\', "{$config['namespace']}\\Test\\{$config['componentPath']}");
            $this->assertFileContains([
                "namespace {$expectedComponentNamespace};",
                "class {$componentName}",
            ], $expectedComponentPath);

            // Should not contain duplicated path segments
            $pathSegments = explode('/', $config['componentPath']);

            $duplicatedPatterns = [];
            foreach ($pathSegments as $segment) {
                $duplicatedPatterns[] = "{$expectedComponentNamespace}\\{$segment}\\{$segment}";
            }

            $this->assertFileNotContains($duplicatedPatterns, $expectedComponentPath);
        }
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // NIVEAU 1: EDGE CASE & INTEGRATION TESTS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    /**
     * Test component generation with complex names
     */
    #[Test]
    public function it_handles_complex_component_names(): void
    {
        $complexCases = [
            'UserProfileCard',
            'BlogPostPreview',
            'ProductCarousel',
            'NavigationMenu',
        ];

        foreach ($complexCases as $componentName) {
            $this->runEasyModulesCommand('make-component', 'Test', $componentName);

            $this->assertFilenameExists("Test/Presentation/Views/Components/{$componentName}.php");

            $this->assertFileContains([
                "class {$componentName} extends Component",
                'namespace App\\Modules\\Test\\Presentation\\Views\\Components;',
            ], "Test/Presentation/Views/Components/{$componentName}.php");
        }
    }

    /**
     * Test component generation edge cases
     */
    #[Test]
    public function it_handles_edge_cases_gracefully(): void
    {
        // Test with single character names
        $this->runEasyModulesCommand('make-component', 'Blog', 'A');

        $this->assertFilenameExists('Blog/Presentation/Views/Components/A.php');
        $this->assertFileContains([
            'class A extends Component',
        ], 'Blog/Presentation/Views/Components/A.php');

        // Test with numbers in names
        $this->runEasyModulesCommand('make-component', 'Blog', 'Button2');

        $this->assertFilenameExists('Blog/Presentation/Views/Components/Button2.php');
        $this->assertFileContains([
            'class Button2 extends Component',
        ], 'Blog/Presentation/Views/Components/Button2.php');
    }

    /**
     * Test component works with various module structures
     */
    #[Test]
    public function it_works_with_various_module_structures(): void
    {
        // Test multiple modules
        $modules = ['Blog', 'Shop', 'Admin'];

        foreach ($modules as $module) {
            $this->runEasyModulesCommand('make-component', $module, 'TestComponent');

            $this->assertFilenameExists("{$module}/Presentation/Views/Components/TestComponent.php");

            $this->assertFileContains([
                "namespace App\\Modules\\{$module}\\Presentation\\Views\\Components;",
                'class TestComponent extends Component',
            ], "{$module}/Presentation/Views/Components/TestComponent.php");
        }
    }

    /**
     * Test multiple components in same module
     */
    #[Test]
    public function it_handles_multiple_components_in_same_module(): void
    {
        $components = [
            'Alert',
            'Button',
            'Card',
            'Forms/Input',
        ];

        foreach ($components as $componentPath) {
            $this->runEasyModulesCommand('make-component', 'Blog', $componentPath);

            $expectedFile = "Blog/Presentation/Views/Components/{$componentPath}.php";
            $this->assertFilenameExists($expectedFile);
        }

        // Verify all files exist and have correct content
        foreach ($components as $componentPath) {
            $expectedFile = "Blog/Presentation/Views/Components/{$componentPath}.php";
            $className    = basename($componentPath);
            $this->assertFileContains([
                "class {$className} extends Component",
            ], $expectedFile);
        }
    }

    /**
     * Test suffix configuration behavior
     */
    #[Test]
    public function it_handles_suffix_configuration(): void
    {
        // Enable suffix appending
        $this->app['config']->set('easymodules.append_suffix', true);
        $this->app['config']->set('easymodules.suffixes.component', 'Component');

        // Test suffix addition when missing
        $this->runEasyModulesCommand('make-component', 'Blog', 'Alert');

        $this->assertFilenameExists('Blog/Presentation/Views/Components/AlertComponent.php');
        $this->assertFileContains([
            'class AlertComponent extends Component',
        ], 'Blog/Presentation/Views/Components/AlertComponent.php');

        // Test suffix not duplicated when already present
        $this->runEasyModulesCommand('make-component', 'Blog', 'ButtonComponent');

        $this->assertFilenameExists('Blog/Presentation/Views/Components/ButtonComponent.php');
        $this->assertFileContains([
            'class ButtonComponent extends Component',
        ], 'Blog/Presentation/Views/Components/ButtonComponent.php');

        // Should NOT create double suffix
        $this->assertFilenameNotExists('Blog/Presentation/Views/Components/ButtonComponentComponent.php');
    }

    /**
     * Test inline component option behavior
     */
    #[Test]
    public function it_handles_inline_option_correctly(): void
    {
        // Regular component should reference a view file
        $this->runEasyModulesCommand('make-component', 'Blog', 'RegularCard');

        $this->assertFileContains([
            "return view('components.regular-card');",
        ], 'Blog/Presentation/Views/Components/RegularCard.php');

        $this->assertFileNotContains([
            "return <<<'blade'",
        ], 'Blog/Presentation/Views/Components/RegularCard.php');

        // Inline component should contain inline template
        $this->runEasyModulesCommand('make-component', 'Blog', 'InlineCard', ['--inline' => true]);

        $this->assertFileContains([
            "return <<<'blade'",
        ], 'Blog/Presentation/Views/Components/InlineCard.php');

        $this->assertFileNotContains([
            "return view('components.inline-card');",
        ], 'Blog/Presentation/Views/Components/InlineCard.php');
    }

    /**
     * Test inline component with nested structure
     */
    #[Test]
    public function it_can_generate_nested_inline_components(): void
    {
        $this->runEasyModulesCommand('make-component', 'Blog', 'Forms/InlineInput', ['--inline' => true]);

        $this->assertFilenameExists('Blog/Presentation/Views/Components/Forms/InlineInput.php');

        $this->assertFileContains([
            'namespace App\\Modules\\Blog\\Presentation\\Views\\Components\\Forms;',
            'class InlineInput extends Component',
            "return <<<'blade'",
        ], 'Blog/Presentation/Views/Components/Forms/InlineInput.php');

        // Should NOT contain view reference for inline components
        $this->assertFileNotContains([
            "return view('components.forms.inline-input');",
        ], 'Blog/Presentation/Views/Components/Forms/InlineInput.php');
    }
}
