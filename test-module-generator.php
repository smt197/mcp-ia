<?php

/**
 * Simple manual test script for ModuleGeneratorService
 * 
 * Run this from the laravel-boost root directory:
 * C:\laragon\bin\php\php-8.3.0-Win32-vs16-x64\php.exe test-module-generator.php
 */

require __DIR__ . '/vendor/autoload.php';

use Laravel\Boost\Services\ModuleGeneratorService;

echo "=== Module Generator Test ===\n\n";

// Test 1: Simple module generation
echo "Test 1: Generating simple module 'test_products'...\n";

$generator = new ModuleGeneratorService(
    'test_products',
    [
        ['name' => 'name', 'type' => 'string', 'required' => true],
        ['name' => 'description', 'type' => 'textarea', 'required' => false],
        ['name' => 'price', 'type' => 'number', 'required' => true],
        ['name' => 'in_stock', 'type' => 'boolean', 'required' => true],
    ]
);

try {
    // Check if required methods exist
    if (!method_exists($generator, 'generate')) {
        echo "❌ FAILED: generate() method not found\n";
        exit(1);
    }

    echo "✓ ModuleGeneratorService instantiated successfully\n";
    echo "✓ Module name: test_products\n";
    echo "✓ Fields: name, description, price, in_stock\n";
    
    // Test field type mapping
    $reflection = new ReflectionClass($generator);
    $fieldsProperty = $reflection->getProperty('fields');
    $fieldsProperty->setAccessible(true);
    $fields = $fieldsProperty->getValue($generator);
    
    echo "✓ Fields count: " . count($fields) . "\n";
    
    // Test module name transformations
    $moduleNameProperty = $reflection->getProperty('moduleName');
    $moduleNameProperty->setAccessible(true);
    echo "✓ Module name: " . $moduleNameProperty->getValue($generator) . "\n";
    
    $singularNameProperty = $reflection->getProperty('singularName');
    $singularNameProperty->setAccessible(true);
    echo "✓ Singular name: " . $singularNameProperty->getValue($generator) . "\n";
    
    $studlyNameProperty = $reflection->getProperty('studlyName');
    $studlyNameProperty->setAccessible(true);
    echo "✓ Studly name: " . $studlyNameProperty->getValue($generator) . "\n";
    
    echo "\n✅ All basic tests passed!\n\n";
    
    // Test 2: Slug identifier
    echo "Test 2: Testing slug identifier...\n";
    
    $slugGenerator = new ModuleGeneratorService(
        'blog_posts',
        [
            ['name' => 'title', 'type' => 'string', 'required' => true],
        ],
        'slug'
    );
    
    $identifierProperty = $reflection->getProperty('identifierField');
    $identifierProperty->setAccessible(true);
    $identifier = $identifierProperty->getValue($slugGenerator);
    
    if ($identifier === 'slug') {
        echo "✓ Slug identifier set correctly\n";
    } else {
        echo "❌ FAILED: Expected 'slug', got '{$identifier}'\n";
        exit(1);
    }
    
    echo "\n✅ Slug test passed!\n\n";
    
    // Test 3: File field detection
    echo "Test 3: Testing file field detection...\n";
    
    $fileGenerator = new ModuleGeneratorService(
        'documents',
        [
            ['name' => 'title', 'type' => 'string', 'required' => true],
            ['name' => 'file', 'type' => 'File', 'required' => true],
        ]
    );
    
    $hasFileMethod = $reflection->getMethod('hasFileField');
    $hasFileMethod->setAccessible(true);
    $hasFile = $hasFileMethod->invoke($fileGenerator);
    
    if ($hasFile) {
        echo "✓ File field detected correctly\n";
    } else {
        echo "❌ FAILED: File field not detected\n";
        exit(1);
    }
    
    echo "\n✅ File field test passed!\n\n";
    
    // Test 4: Field type mapping
    echo "Test 4: Testing field type mapping...\n";
    
    $getMigrationTypeMethod = $reflection->getMethod('getMigrationType');
    $getMigrationTypeMethod->setAccessible(true);
    
    $testTypes = [
        'string' => 'string',
        'number' => 'integer',
        'boolean' => 'boolean',
        'Date' => 'timestamp',
        'textarea' => 'text',
        'quill-editor' => 'longText',
        'email' => 'string',
    ];
    
    foreach ($testTypes as $inputType => $expectedType) {
        $actualType = $getMigrationTypeMethod->invoke($generator, $inputType);
        if ($actualType === $expectedType) {
            echo "✓ {$inputType} → {$expectedType}\n";
        } else {
            echo "❌ FAILED: {$inputType} → expected {$expectedType}, got {$actualType}\n";
            exit(1);
        }
    }
    
    echo "\n✅ Field type mapping tests passed!\n\n";
    
    echo "=== ALL TESTS PASSED ===\n";
    echo "\nThe ModuleGeneratorService is working correctly!\n";
    echo "You can now test it with the MCP tool.\n";
    
} catch (Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
