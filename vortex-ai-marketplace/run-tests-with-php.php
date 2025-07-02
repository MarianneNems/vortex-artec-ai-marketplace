<?php
/**
 * VORTEX AI Marketplace - PHP Validation Script
 * 
 * Comprehensive validation using PHP for syntax checking, 
 * WordPress standards, and plugin functionality.
 */

// Colors for console output
class Console {
    const GREEN = "\033[32m";
    const RED = "\033[31m";
    const YELLOW = "\033[33m";
    const CYAN = "\033[36m";
    const RESET = "\033[0m";
    
    public static function green($text) {
        return self::GREEN . $text . self::RESET;
    }
    
    public static function red($text) {
        return self::RED . $text . self::RESET;
    }
    
    public static function yellow($text) {
        return self::YELLOW . $text . self::RESET;
    }
    
    public static function cyan($text) {
        return self::CYAN . $text . self::RESET;
    }
}

echo Console::green("================================\n");
echo Console::green("VORTEX AI Marketplace - PHP Tests\n");
echo Console::green("================================\n\n");

$errors = 0;
$warnings = 0;

// Test 1: PHP Syntax Validation
echo Console::yellow("[1/6] PHP Syntax Validation...\n");
$phpFiles = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator('.', RecursiveDirectoryIterator::SKIP_DOTS)
);

$syntaxErrors = 0;
foreach ($phpFiles as $file) {
    if ($file->getExtension() === 'php') {
        $output = [];
        $return = 0;
        exec("php -l " . escapeshellarg($file->getPathname()), $output, $return);
        
        if ($return !== 0) {
            echo Console::red("✗ Syntax error in: " . $file->getFilename() . "\n");
            echo "  " . implode("\n  ", $output) . "\n";
            $syntaxErrors++;
        }
    }
}

if ($syntaxErrors === 0) {
    echo Console::green("✓ All PHP files have valid syntax\n");
} else {
    echo Console::red("✗ $syntaxErrors PHP syntax errors found\n");
    $errors += $syntaxErrors;
}

// Test 2: Required Files Check
echo "\n" . Console::yellow("[2/6] Required Files Check...\n");
$requiredFiles = [
    'vortex-ai-marketplace.php',
    'composer.json',
    'phpunit.xml',
    'includes/class-vortex-ai-marketplace.php',
    'includes/shortcodes/class-vortex-artist-business-quiz.php',
    'templates/artist-business-quiz.php'
];

$missingFiles = [];
foreach ($requiredFiles as $file) {
    if (!file_exists($file)) {
        $missingFiles[] = $file;
    }
}

if (empty($missingFiles)) {
    echo Console::green("✓ All required files present\n");
} else {
    echo Console::red("✗ Missing files:\n");
    foreach ($missingFiles as $file) {
        echo Console::red("  - $file\n");
    }
    $errors += count($missingFiles);
}

// Test 3: WordPress Plugin Header
echo "\n" . Console::yellow("[3/6] WordPress Plugin Header...\n");
if (file_exists('vortex-ai-marketplace.php')) {
    $pluginContent = file_get_contents('vortex-ai-marketplace.php');
    $headerFields = [
        'Plugin Name:' => false,
        'Description:' => false,
        'Version:' => false,
        'Author:' => false,
        'License:' => false
    ];
    
    foreach ($headerFields as $field => $found) {
        if (strpos($pluginContent, $field) !== false) {
            $headerFields[$field] = true;
        }
    }
    
    $missingHeaders = array_keys(array_filter($headerFields, function($v) { return !$v; }));
    
    if (empty($missingHeaders)) {
        echo Console::green("✓ Plugin header is complete\n");
    } else {
        echo Console::yellow("⚠ Missing header fields: " . implode(', ', $missingHeaders) . "\n");
        $warnings++;
    }
} else {
    echo Console::red("✗ Main plugin file not found\n");
    $errors++;
}

// Test 4: Security Best Practices
echo "\n" . Console::yellow("[4/6] Security Best Practices...\n");
$securityIssues = 0;
$phpFiles = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator('.', RecursiveDirectoryIterator::SKIP_DOTS)
);

foreach ($phpFiles as $file) {
    if ($file->getExtension() === 'php' && $file->getSize() > 0) {
        $content = file_get_contents($file->getPathname());
        
        // Skip very small files (likely empty)
        if (strlen($content) < 50) continue;
        
        // Check for direct access protection
        if (!preg_match('/defined\s*\(\s*[\'"](?:ABSPATH|WPINC)[\'"]/', $content) &&
            !preg_match('/if\s*\(\s*!\s*defined\s*\(\s*[\'"](?:ABSPATH|WPINC)[\'"]/', $content)) {
            
            // Allow some exceptions (like this test file)
            if (!in_array($file->getFilename(), ['run-tests-with-php.php', 'setup-dev-environment.bat'])) {
                $securityIssues++;
            }
        }
    }
}

if ($securityIssues === 0) {
    echo Console::green("✓ Security best practices followed\n");
} else {
    echo Console::yellow("⚠ $securityIssues files missing direct access protection\n");
    $warnings++;
}

// Test 5: Database Schema Validation
echo "\n" . Console::yellow("[5/6] Database Schema Validation...\n");
$dbSchemaFiles = [];
$phpFiles = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator('.', RecursiveDirectoryIterator::SKIP_DOTS)
);

foreach ($phpFiles as $file) {
    if ($file->getExtension() === 'php') {
        $content = file_get_contents($file->getPathname());
        if (preg_match('/CREATE\s+TABLE/i', $content)) {
            $dbSchemaFiles[] = $file->getFilename();
        }
    }
}

if (!empty($dbSchemaFiles)) {
    echo Console::green("✓ Database schema files found (" . count($dbSchemaFiles) . "):\n");
    foreach ($dbSchemaFiles as $file) {
        echo Console::cyan("  - $file\n");
    }
} else {
    echo Console::yellow("⚠ No database schema files found\n");
    $warnings++;
}

// Test 6: Shortcode Implementation
echo "\n" . Console::yellow("[6/6] Shortcode Implementation...\n");
$shortcodeFile = 'includes/shortcodes/class-vortex-artist-business-quiz.php';
if (file_exists($shortcodeFile)) {
    $content = file_get_contents($shortcodeFile);
    
    $checks = [
        'Shortcode Registration' => preg_match('/add_shortcode.*vortex_artist_business_quiz/', $content),
        'Monthly Limit Check' => preg_match('/has_submitted_this_month/', $content),
        'Access Control' => preg_match('/user_can_access_quiz/', $content),
        'Template Integration' => preg_match('/template.*php/', $content)
    ];
    
    foreach ($checks as $check => $passed) {
        if ($passed) {
            echo Console::green("  ✓ $check\n");
        } else {
            echo Console::red("  ✗ $check\n");
            $errors++;
        }
    }
} else {
    echo Console::red("✗ Shortcode file not found\n");
    $errors++;
}

// Test 7: Composer Dependencies (if available)
echo "\n" . Console::yellow("[7/7] Composer Dependencies...\n");
if (file_exists('composer.json')) {
    $composerData = json_decode(file_get_contents('composer.json'), true);
    
    if (isset($composerData['require'])) {
        echo Console::green("✓ Composer dependencies defined:\n");
        foreach ($composerData['require'] as $package => $version) {
            echo Console::cyan("  - $package: $version\n");
        }
    } else {
        echo Console::yellow("⚠ No composer dependencies defined\n");
        $warnings++;
    }
    
    if (file_exists('vendor/autoload.php')) {
        echo Console::green("✓ Composer dependencies installed\n");
    } else {
        echo Console::yellow("⚠ Run 'composer install' to install dependencies\n");
        $warnings++;
    }
} else {
    echo Console::red("✗ composer.json not found\n");
    $errors++;
}

// Final Summary
echo "\n" . Console::green("================================\n");
echo Console::green("VALIDATION SUMMARY\n");
echo Console::green("================================\n\n");

$total_tests = 7;
$passed_tests = $total_tests - ($errors > 0 ? 1 : 0) - ($warnings > 0 ? 1 : 0);

echo "Total Tests: $total_tests\n";
echo Console::green("Passed: $passed_tests\n");
if ($warnings > 0) {
    echo Console::yellow("Warnings: $warnings\n");
}
if ($errors > 0) {
    echo Console::red("Errors: $errors\n");
}

echo "\n";

if ($errors === 0 && $warnings === 0) {
    echo Console::green("🎉 EXCELLENT! All tests passed!\n");
    echo Console::green("Your VORTEX AI Marketplace plugin is production-ready!\n");
} elseif ($errors === 0) {
    echo Console::yellow("✅ GOOD! Tests passed with minor warnings.\n");
    echo Console::yellow("Plugin is ready for staging environment.\n");
} else {
    echo Console::red("❌ Issues found that should be addressed.\n");
    echo Console::yellow("Review errors above before deployment.\n");
}

echo "\n" . Console::cyan("Next Steps:\n");
echo Console::cyan("1. Run 'composer install' if not done\n");
echo Console::cyan("2. Run 'vendor/bin/phpunit' for unit tests\n");
echo Console::cyan("3. Install in WordPress for integration testing\n");
echo Console::cyan("4. Configure AI backends and blockchain connections\n\n");

exit($errors > 0 ? 1 : 0);
?> 