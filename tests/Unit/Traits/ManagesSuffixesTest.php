<?php

declare(strict_types=1);

namespace Kadevland\EasyModules\Tests\Unit\Traits;

use Kadevland\EasyModules\Tests\Support\TestCase;
use Kadevland\EasyModules\Traits\ManagesSuffixes;
use PHPUnit\Framework\Attributes\Test;

class ManagesSuffixesTest extends TestCase
{
    use ManagesSuffixes;

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // BASIC SUFFIX FUNCTIONALITY TESTS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    #[Test]
    public function it_adds_suffix_if_missing(): void
    {
        $cases = [
            // [name, suffix, expected]
            ['User', 'Controller', 'UserController'],
            ['Post', 'Service', 'PostService'],
            ['UserController', 'Controller', 'UserController'], // Already present
            ['Product', 'Factory', 'ProductFactory'],
            ['ProductFactory', 'Factory', 'ProductFactory'], // Already present
        ];

        foreach ($cases as [$name, $suffix, $expected]) {
            $result = $this->addSuffixIfMissing($name, $suffix);
            $this->assertEquals($expected, $result, "Failed for name: {$name}, suffix: {$suffix}");
        }
    }

    #[Test]
    public function it_handles_empty_strings(): void
    {
        $result = $this->addSuffixIfMissing('', 'Controller');
        $this->assertEquals('Controller', $result);

        $result = $this->addSuffixIfMissing('User', '');
        $this->assertEquals('User', $result);

        $result = $this->addSuffixIfMissing('', '');
        $this->assertEquals('', $result);
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // CASE HANDLING TESTS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    #[Test]
    public function it_handles_case_insensitive_matching(): void
    {
        $cases = [
            // Test different case variants
            ['USERCONTROLLER', 'Controller', 'USERCONTROLLER'], // Case insensitive
            ['usercontroller', 'Controller', 'usercontroller'], // Case insensitive
            ['UserCONTROLLER', 'Controller', 'UserCONTROLLER'],
            ['UserController', 'CONTROLLER', 'UserController'],
            ['User', 'CONTROLLER', 'UserCONTROLLER'],
        ];

        foreach ($cases as [$name, $suffix, $expected]) {
            $result = $this->addSuffixIfMissing($name, $suffix);
            $this->assertEquals($expected, $result, "Failed for name: {$name}, suffix: {$suffix}");
        }
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // PARTIAL MATCHES AND SPECIAL CASES TESTS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    #[Test]
    public function it_handles_partial_matches(): void
    {
        // Test that partial matches are not considered matches
        $cases = [
            ['Control', 'Controller', 'ControlController'], // "Control" does not end with "Controller"
            ['UserCont', 'Controller', 'UserContController'], // "UserCont" does not end with "Controller"
            ['Service', 'Vice', 'Service'], // "Service" does indeed end with "Vice"
            ['Factory', 'ory', 'Factory'], // "Factory" does indeed end with "ory"
        ];

        foreach ($cases as [$name, $suffix, $expected]) {
            $result = $this->addSuffixIfMissing($name, $suffix);
            $this->assertEquals($expected, $result, "Failed for name: {$name}, suffix: {$suffix}");
        }
    }

    #[Test]
    public function it_handles_special_characters(): void
    {
        $cases = [
            ['User_Model', '_Model', 'User_Model'],
            ['User', '_Controller', 'User_Controller'],
            ['Post-Service', '-Service', 'Post-Service'],
            ['Blog', '-Handler', 'Blog-Handler'],
        ];

        foreach ($cases as [$name, $suffix, $expected]) {
            $result = $this->addSuffixIfMissing($name, $suffix);
            $this->assertEquals($expected, $result, "Failed for name: {$name}, suffix: {$suffix}");
        }
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // EDGE CASES AND NUMERIC TESTS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    #[Test]
    public function it_handles_numeric_suffixes(): void
    {
        $cases = [
            ['Version', '2', 'Version2'],
            ['Version2', '2', 'Version2'],
            ['Test', '123', 'Test123'],
            ['Test123', '123', 'Test123'],
        ];

        foreach ($cases as [$name, $suffix, $expected]) {
            $result = $this->addSuffixIfMissing($name, $suffix);
            $this->assertEquals($expected, $result, "Failed for name: {$name}, suffix: {$suffix}");
        }
    }
}
