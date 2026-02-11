<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use Laravel\Boost\Services\ModuleGeneratorService;

beforeEach(function (): void {
    // Clean up any test files that might exist
    $testPaths = [
        app_path('Models/TestProduct.php'),
        app_path('Http/Controllers/TestProductController.php'),
        app_path('Http/Resources/TestProductResource.php'),
        app_path('Http/Resources/Collections/TestProductCollection.php'),
        app_path('Http/Requests/TestProductRequest.php'),
        app_path('Policies/TestProductPolicy.php'),
        database_path('factories/TestProductFactory.php'),
        database_path('seeders/TestProductSeeder.php'),
    ];

    foreach ($testPaths as $path) {
        if (File::exists($path)) {
            File::delete($path);
        }
    }

    // Clean up migrations
    $migrations = File::glob(database_path('migrations/*_create_test_products_table.php'));

    foreach ($migrations as $migration) {
        File::delete($migration);
    }
});

afterEach(function (): void {
    // Clean up test files after each test
    $testPaths = [
        app_path('Models/TestProduct.php'),
        app_path('Http/Controllers/TestProductController.php'),
        app_path('Http/Resources/TestProductResource.php'),
        app_path('Http/Resources/Collections/TestProductCollection.php'),
        app_path('Http/Requests/TestProductRequest.php'),
        app_path('Policies/TestProductPolicy.php'),
        database_path('factories/TestProductFactory.php'),
        database_path('seeders/TestProductSeeder.php'),
    ];

    foreach ($testPaths as $path) {
        if (File::exists($path)) {
            File::delete($path);
        }
    }

    // Clean up migrations
    $migrations = File::glob(database_path('migrations/*_create_test_products_table.php'));

    foreach ($migrations as $migration) {
        File::delete($migration);
    }
});

test('it generates a simple module successfully', function (): void {
    $generator = new ModuleGeneratorService(
        'test_products',
        [
            ['name' => 'name', 'type' => 'string', 'required' => true],
            ['name' => 'price', 'type' => 'number', 'required' => true],
        ]
    );

    // Disable auto migration and seeding for tests
    config(['boost.module_generator.auto_migrate' => false]);
    config(['boost.module_generator.auto_seed' => false]);

    $result = $generator->generate();

    expect($result)
        ->toBeArray()
        ->toHaveKey('success', true)
        ->toHaveKey('message')
        ->toHaveKey('files');

    // Verify model was created
    expect(File::exists(app_path('Models/TestProduct.php')))->toBeTrue();

    // Verify controller was created
    expect(File::exists(app_path('Http/Controllers/TestProductController.php')))->toBeTrue();

    // Verify migration was created
    $migrations = File::glob(database_path('migrations/*_create_test_products_table.php'));
    expect($migrations)->toHaveCount(1);

    // Verify factory was created
    expect(File::exists(database_path('factories/TestProductFactory.php')))->toBeTrue();

    // Verify seeder was created
    expect(File::exists(database_path('seeders/TestProductSeeder.php')))->toBeTrue();
});

test('it generates model with correct fillable fields', function (): void {
    $generator = new ModuleGeneratorService(
        'test_products',
        [
            ['name' => 'name', 'type' => 'string', 'required' => true],
            ['name' => 'description', 'type' => 'textarea', 'required' => false],
            ['name' => 'price', 'type' => 'number', 'required' => true],
        ]
    );

    config(['boost.module_generator.auto_migrate' => false]);
    config(['boost.module_generator.auto_seed' => false]);

    $result = $generator->generate();

    expect($result['success'])->toBeTrue();

    $modelContent = File::get(app_path('Models/TestProduct.php'));

    expect($modelContent)
        ->toContain('class TestProduct')
        ->toContain("'name'")
        ->toContain("'description'")
        ->toContain("'price'");
});

test('it generates model with slug support', function (): void {
    $generator = new ModuleGeneratorService(
        'test_products',
        [
            ['name' => 'title', 'type' => 'string', 'required' => true],
        ],
        'slug'
    );

    config(['boost.module_generator.auto_migrate' => false]);
    config(['boost.module_generator.auto_seed' => false]);

    $result = $generator->generate();

    expect($result['success'])->toBeTrue();

    $modelContent = File::get(app_path('Models/TestProduct.php'));

    expect($modelContent)
        ->toContain('use Spatie\Sluggable\HasSlug')
        ->toContain('use Spatie\Sluggable\SlugOptions')
        ->toContain('getSlugOptions()')
        ->toContain("'slug'");
});

test('it generates migration with correct fields', function (): void {
    $generator = new ModuleGeneratorService(
        'test_products',
        [
            ['name' => 'name', 'type' => 'string', 'required' => true],
            ['name' => 'description', 'type' => 'textarea', 'required' => false],
            ['name' => 'price', 'type' => 'number', 'required' => true],
            ['name' => 'in_stock', 'type' => 'boolean', 'required' => true],
        ]
    );

    config(['boost.module_generator.auto_migrate' => false]);
    config(['boost.module_generator.auto_seed' => false]);

    $result = $generator->generate();

    expect($result['success'])->toBeTrue();

    $migrations = File::glob(database_path('migrations/*_create_test_products_table.php'));
    $migrationContent = File::get($migrations[0]);

    expect($migrationContent)
        ->toContain('create_test_products_table')
        ->toContain("\$table->string('name')")
        ->toContain("\$table->text('description')->nullable()")
        ->toContain("\$table->integer('price')")
        ->toContain("\$table->boolean('in_stock')");
});

test('it generates controller with correct configuration', function (): void {
    $generator = new ModuleGeneratorService(
        'test_products',
        [
            ['name' => 'name', 'type' => 'string', 'required' => true],
        ]
    );

    config(['boost.module_generator.auto_migrate' => false]);
    config(['boost.module_generator.auto_seed' => false]);

    $result = $generator->generate();

    expect($result['success'])->toBeTrue();

    $controllerContent = File::get(app_path('Http/Controllers/TestProductController.php'));

    expect($controllerContent)
        ->toContain('class TestProductController extends Controller')
        ->toContain('protected $model = TestProduct::class')
        ->toContain('protected $resource = TestProductResource::class')
        ->toContain('protected $collectionResource = TestProductCollection::class')
        ->toContain('protected $request = TestProductRequest::class');
});

test('it generates validation rules correctly', function (): void {
    $generator = new ModuleGeneratorService(
        'test_products',
        [
            ['name' => 'name', 'type' => 'string', 'required' => true],
            ['name' => 'email', 'type' => 'email', 'required' => false],
            ['name' => 'price', 'type' => 'number', 'required' => true],
        ]
    );

    config(['boost.module_generator.auto_migrate' => false]);
    config(['boost.module_generator.auto_seed' => false]);

    $result = $generator->generate();

    expect($result['success'])->toBeTrue();

    $requestContent = File::get(app_path('Http/Requests/TestProductRequest.php'));

    expect($requestContent)
        ->toContain('storeRules()')
        ->toContain('updateRules()')
        ->toContain("'name'")
        ->toContain("'email'")
        ->toContain("'price'");
});
