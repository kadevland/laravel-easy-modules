<?php

declare(strict_types=1);

namespace Kadevland\EasyModules\Tests\Unit\Traits;

use Kadevland\EasyModules\Tests\Support\TestCase;
use Kadevland\EasyModules\Traits\PathNamespaceConverter;
use PHPUnit\Framework\Attributes\Test;

class PathNamespaceConverterTest extends TestCase
{
    use PathNamespaceConverter;

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // BASIC PATH TO NAMESPACE CONVERSION TESTS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    #[Test]
    public function it_converts_path_to_namespace(): void
    {
        $cases = [
            'simple'                        => 'simple',
            'with/slash'                    => 'with\\slash',
            'Presentation/Http/Controllers' => 'Presentation\\Http\\Controllers',
            'already\\namespace'            => 'already\\namespace',
            'mixed/slash\\backslash'        => 'mixed\\slash\\backslash',
        ];

        foreach ($cases as $input => $expected) {
            $result = $this->pathToNamespace($input);
            $this->assertEquals($expected, $result, "Failed for input: {$input}");
        }
    }

    #[Test]
    public function it_converts_to_studly_namespace(): void
    {
        $cases = [
            'simple'                        => 'Simple',
            'with/slash'                    => 'With\\Slash',
            'presentation/Http/controllers' => 'Presentation\\Http\\Controllers',
            'Already\\Studly\\Namespace'    => 'Already\\Studly\\Namespace',
            'snake_case_path'               => 'Snake\\Case\\Path', // Underscores create segments
            'kebab-case/path'               => 'Kebab\\Case\\Path', // Hyphens too
        ];

        foreach ($cases as $input => $expected) {
            $result = $this->toStudlyNamespace($input, '\\');
            $this->assertEquals($expected, $result, "Failed for input: {$input}");
        }
    }

    #[Test]
    public function it_converts_to_studly_namespace_with_custom_separator(): void
    {
        $cases = [
            'simple'                   => 'Simple',
            'with/slash'               => 'With/Slash',
            'presentation/controllers' => 'Presentation/Controllers',
            'snake_case_path'          => 'Snake/Case/Path', // Underscores create segments
        ];

        foreach ($cases as $input => $expected) {
            $result = $this->toStudlyNamespace($input, '/');
            $this->assertEquals($expected, $result, "Failed for input: {$input}");
        }
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // COMPREHENSIVE CONVERSION WITH OPTIONS TESTS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    #[Test]
    public function it_converts_path_to_namespace_with_options(): void
    {
        // Test normal conversion
        $result = $this->convertPathToNamespace('Domain/Entities');
        $this->assertEquals('Domain\\Entities', $result);

        // Test StudlyCase conversion (underscores create segments)
        $result = $this->convertPathToNamespace('application_services', true);
        $this->assertEquals('Application\\Services', $result);

        // Test StudlyCase conversion with custom separator
        $result = $this->convertPathToNamespace('domain/entities', true, '/');
        $this->assertEquals('Domain/Entities', $result);

        // Test normal conversion with explicit options
        $result = $this->convertPathToNamespace('Infrastructure/Models', false, '\\');
        $this->assertEquals('Infrastructure\\Models', $result);
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // PATH NORMALIZATION AND MANIPULATION TESTS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    #[Test]
    public function it_normalizes_paths(): void
    {
        $cases = [
            '//Domain//Entities/'           => 'Domain/Entities',
            '\\Application\\\\Services\\'   => 'Application/Services',
            '/mixed\\\\path//with/slashes/' => 'mixed/path/with/slashes',
            'clean/path'                    => 'clean/path',
            ''                              => '',
            '/'                             => '',
            '\\'                            => '',
        ];

        foreach ($cases as $input => $expected) {
            $result = $this->normalizePath($input);
            $this->assertEquals($expected, $result, "Failed for input: {$input}");
        }
    }

    #[Test]
    public function it_splits_namespace(): void
    {
        $cases = [
            'App\\Modules\\Blog\\Domain\\Entities' => ['App', 'Modules', 'Blog', 'Domain', 'Entities'],
            'Domain/Services/UserService'          => ['Domain', 'Services', 'UserService'],
            'Simple'                               => ['Simple'],
            ''                                     => [],
            '//Multiple///Slashes//'               => ['Multiple', 'Slashes'],
        ];

        foreach ($cases as $input => $expected) {
            $result = $this->splitNamespace($input);
            $this->assertEquals($expected, $result, "Failed for input: {$input}");
        }
    }

    #[Test]
    public function it_joins_namespace(): void
    {
        $cases = [
            // [segments, separator, expected]
            [['Domain', 'Entities', 'User'], '\\', 'Domain\\Entities\\User'],
            [['App', 'Modules', 'Blog'], '/', 'App/Modules/Blog'],
            [['Single'], '\\', 'Single'],
            [[], '\\', ''],
            [['', 'Domain', '', 'Entities'], '\\', 'Domain\\Entities'], // Filters empty segments
        ];

        foreach ($cases as [$segments, $separator, $expected]) {
            $result = $this->joinNamespace($segments, $separator);
            $this->assertEquals($expected, $result, "Failed for segments: ".implode(',', $segments)." with separator: {$separator}");
        }
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // FILE PATH GENERATION TESTS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    #[Test]
    public function it_generates_php_file_path(): void
    {
        $cases = [
            'UserController'      => 'UserController.php',
            'UserController.stub' => 'UserController.php',
            'UserController.php'  => 'UserController.php',
            'path/to/Controller'  => 'path/to/Controller.php',
            'path/to/file.stub'   => 'path/to/file.php',
            'file.txt'            => 'file.txt', // Keeps existing extension
            'simple'              => 'simple.php',
            'with/slash'          => 'with/slash.php',
            'already/file.php'    => 'already/file.php',
            'file.stub'           => 'file.php',
        ];

        foreach ($cases as $input => $expected) {
            $result = $this->generatePhpFilePath($input);
            $this->assertEquals($expected, $result, "Failed for input: {$input}");
        }
    }

    #[Test]
    public function it_ensures_file_extension(): void
    {
        // Test adding extension
        $result = $this->ensureFileExtension('config', 'php');
        $this->assertEquals('config.php', $result);

        // Test replacing extension
        $result = $this->ensureFileExtension('test.stub', 'php', true);
        $this->assertEquals('test.php', $result);

        // Test keeping existing extension
        $result = $this->ensureFileExtension('file.txt', 'php', false);
        $this->assertEquals('file.txt', $result);

        // Test with extension starting with a dot
        $result = $this->ensureFileExtension('config', '.yaml');
        $this->assertEquals('config.yaml', $result);
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // COMPLETE FILE PATH CONSTRUCTION TESTS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    #[Test]
    public function it_builds_file_path_from_namespace(): void
    {
        $result = $this->buildFilePathFromNamespace(
            'App\\Domain\\Entities',
            'User',
            'php',
            '/var/www'
        );

        $expected = '/var/www'.DIRECTORY_SEPARATOR.
            'App'.DIRECTORY_SEPARATOR.
            'Domain'.DIRECTORY_SEPARATOR.
            'Entities'.DIRECTORY_SEPARATOR.
            'User.php';

        $this->assertEquals($expected, $result);
    }

    #[Test]
    public function it_builds_file_path_from_namespace_without_base_path(): void
    {
        $result = $this->buildFilePathFromNamespace(
            'Domain\\Services',
            'UserService',
            'php'
        );

        $expected = 'Domain'.DIRECTORY_SEPARATOR.
            'Services'.DIRECTORY_SEPARATOR.
            'UserService.php';

        $this->assertEquals($expected, $result);
    }

    #[Test]
    public function it_builds_file_path_with_custom_extension(): void
    {
        $result = $this->buildFilePathFromNamespace(
            'Config\\Modules',
            'blog_config',
            'yaml',
            '/app'
        );

        $expected = '/app'.DIRECTORY_SEPARATOR.
            'Config'.DIRECTORY_SEPARATOR.
            'Modules'.DIRECTORY_SEPARATOR.
            'blog_config.yaml';

        $this->assertEquals($expected, $result);
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // EDGE CASES AND SPECIAL SCENARIOS TESTS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    #[Test]
    public function it_handles_edge_cases_and_special_scenarios(): void
    {
        // Test with empty namespace
        $result = $this->buildFilePathFromNamespace('', 'file', 'php');
        $this->assertEquals('file.php', $result);

        // Test with filename that already has an extension (don't add extension)
        $result = $this->buildFilePathFromNamespace('App\\Services', 'UserService.php', 'php');
        $this->assertEquals('App'.DIRECTORY_SEPARATOR.'Services'.DIRECTORY_SEPARATOR.'UserService.php', $result);

        // Test path normalization with spaces
        $result = $this->normalizePath('  /Domain//Entities/  ');
        $this->assertEquals('Domain/Entities', $result);

        // Test joinNamespace with segments containing spaces
        $result = $this->joinNamespace([' Domain ', ' Entities '], '\\');
        $this->assertEquals(' Domain \\ Entities ', $result); // Spaces are preserved

        // Test with underscores in a single segment (becomes multiple segments)
        $result = $this->toStudlyNamespace('single_segment');
        $this->assertEquals('Single\\Segment', $result);
    }
}
