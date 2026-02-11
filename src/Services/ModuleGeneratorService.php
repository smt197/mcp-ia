<?php

declare(strict_types=1);

namespace Laravel\Boost\Services;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ModuleGeneratorService
{
    protected string $moduleName;

    protected string $singularName;

    protected string $studlyName;

    protected string $studlySingular;

    protected array $fields;

    protected string $identifierField;

    protected array $roles;

    public function __construct(string $moduleName, array $fields, string $identifierField = 'id', array $roles = ['user'])
    {
        $this->moduleName = $moduleName;
        $this->singularName = Str::singular($moduleName);
        $this->studlyName = Str::studly($moduleName);
        $this->studlySingular = Str::studly($this->singularName);
        $this->fields = $fields;
        $this->identifierField = $identifierField;
        $this->roles = $roles;
    }

    public function generate(): array
    {
        $results = [];

        try {
            $results['model'] = $this->generateModel();
            $results['migration'] = $this->generateMigration();
            $results['factory'] = $this->generateFactory();
            $results['controller'] = $this->generateController();
            $results['resource'] = $this->generateResource();
            $results['collection'] = $this->generateCollection();
            $results['request'] = $this->generateRequest();
            $results['policy'] = $this->generatePolicy();
            $results['seeder'] = $this->generateSeeder();
            $results['route'] = $this->addRoute();

            // Run migration automatically if configured
            if (config('boost.module_generator.auto_migrate', true)) {
                $migrationResult = $this->runMigration();
                $results['migration_executed'] = $migrationResult;
            }

            // Run seeder automatically if configured
            if (config('boost.module_generator.auto_seed', true)) {
                $seederResult = $this->runSeeder();
                $results['seeder_executed'] = $seederResult;
            }

            return [
                'success' => true,
                'message' => 'Module generated successfully',
                'files' => $results,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Module generation failed: '.$e->getMessage(),
                'error' => $e->getTraceAsString(),
            ];
        }
    }

    protected function hasFileField(): bool
    {
        foreach ($this->fields as $field) {
            if ($field['type'] === 'File') {
                return true;
            }
        }

        return false;
    }

    protected function getFileFields(): array
    {
        return array_filter($this->fields, fn ($field) => $field['type'] === 'File');
    }

    protected function generateModel(): string
    {
        $fillable = $this->getFillableFields();
        $casts = $this->getCasts();

        $useSlug = '';
        $useMedia = '';
        $implements = '';
        $useTrait = 'HasFactory';
        $slugMethod = '';
        $mediaMethod = '';

        if ($this->identifierField === 'slug') {
            $useSlug = "use Spatie\\Sluggable\\HasSlug;\nuse Spatie\\Sluggable\\SlugOptions;\n";
            $useTrait = 'HasFactory, HasSlug';
            $firstFieldName = $this->fields[0]['name'] ?? 'name';
            $slugMethod = "

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom(['{$firstFieldName}'])
            ->saveSlugsTo('slug')
            ->doNotGenerateSlugsOnUpdate();
    }";
        }

        if ($this->hasFileField()) {
            $useMedia = "use Spatie\\MediaLibrary\\HasMedia;\nuse Spatie\\MediaLibrary\\InteractsWithMedia;\n";
            $implements = ' implements HasMedia';
            $useTrait .= ', InteractsWithMedia';

            $diskName = Str::snake($this->moduleName);
            $mediaMethod = "

    /**
     * Register media collections.
     */
    public function registerMediaCollections(): void
    {
        \$this->addMediaCollection('{$diskName}')
            ->acceptsMimeTypes(['image/png', 'image/jpg', 'image/jpeg', 'application/pdf'])
            ->useDisk('{$diskName}');
    }";
        }

        $content = "<?php

declare(strict_types=1);

namespace App\\Models;

use Illuminate\\Database\\Eloquent\\Factories\\HasFactory;
use Illuminate\\Database\\Eloquent\\Model;
{$useSlug}{$useMedia}
class {$this->studlySingular} extends Model{$implements}
{
    use {$useTrait};

    protected \$fillable = [{$fillable}];

    protected function casts(): array
    {
        return [{$casts}
        ];
    }{$slugMethod}{$mediaMethod}
}
";

        $path = app_path("Models/{$this->studlySingular}.php");
        File::put($path, $content);

        return $path;
    }

    protected function generateMigration(): string
    {
        $timestamp = date('Y_m_d_His');
        $tableName = Str::snake($this->moduleName);
        $fields = $this->getMigrationFields();

        $content = "<?php

declare(strict_types=1);

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('{$tableName}', function (Blueprint \$table) {
            \$table->id();{$fields}
            \$table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('{$tableName}');
    }
};
";

        $path = database_path("migrations/{$timestamp}_create_{$tableName}_table.php");
        File::put($path, $content);

        return $path;
    }

    protected function generateFactory(): string
    {
        $factoryFields = $this->getFactoryDefinition();

        $content = "<?php

declare(strict_types=1);

namespace Database\\Factories;

use App\\Models\\{$this->studlySingular};
use Illuminate\\Database\\Eloquent\\Factories\\Factory;

/**
 * @extends \\Illuminate\\Database\\Eloquent\\Factories\\Factory<\\App\\Models\\{$this->studlySingular}>
 */
class {$this->studlySingular}Factory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
{$factoryFields}
        ];
    }
}
";

        $path = database_path("factories/{$this->studlySingular}Factory.php");
        File::put($path, $content);

        return $path;
    }

    protected function generateController(): string
    {
        $keyName = $this->identifierField === 'slug' ? 'slug' : 'id';

        // Exclude File fields for searchable and sortable
        $fieldsWithoutFile = array_filter($this->fields, fn ($field) => $field['type'] !== 'File');
        $searchable = implode("', '", array_column(array_slice($fieldsWithoutFile, 0, 3), 'name'));
        $sortable = implode("', '", array_column(array_slice($fieldsWithoutFile, 0, 2), 'name'));

        $keyMethod = $this->identifierField === 'slug' ? "
    public function keyName(): string
    {
        return 'slug';
    }

    " : '';

        $content = "<?php

declare(strict_types=1);

namespace App\\Http\\Controllers;

use App\\Http\\Requests\\{$this->studlySingular}Request;
use App\\Http\\Resources\\{$this->studlySingular}Resource;
use App\\Http\\Resources\\Collections\\{$this->studlySingular}Collection;
use App\\Models\\{$this->studlySingular};
use Orion\\Http\\Controllers\\Controller;
use Orion\\Concerns\\DisableAuthorization;

class {$this->studlySingular}Controller extends Controller
{
    use DisableAuthorization;

    protected \$model = {$this->studlySingular}::class;

    protected \$resource = {$this->studlySingular}Resource::class;

    protected \$collectionResource = {$this->studlySingular}Collection::class;

    protected \$request = {$this->studlySingular}Request::class;
{$keyMethod}
    public function limit(): int
    {
        return config('app.limit_pagination', 15);
    }

    public function maxLimit(): int
    {
        return config('app.max_pagination', 100);
    }

    public function searchableBy(): array
    {
        return ['{$searchable}'];
    }

    public function sortableBy(): array
    {
        return ['{$sortable}', 'created_at'];
    }

    public function filterableBy(): array
    {
        return ['{$searchable}'];
    }
}
";

        $path = app_path("Http/Controllers/{$this->studlySingular}Controller.php");
        File::put($path, $content);

        return $path;
    }

    // Helper methods will be added in the next part...
    protected function getFillableFields(): string
    {
        $fieldsWithoutFiles = array_filter($this->fields, fn ($field) => $field['type'] !== 'File');
        $fillable = array_map(fn ($field) => "'{$field['name']}'", $fieldsWithoutFiles);

        if ($this->identifierField === 'slug') {
            array_unshift($fillable, "'slug'");
        }

        return implode(', ', $fillable);
    }

    protected function getCasts(): string
    {
        $casts = [];
        foreach ($this->fields as $field) {
            if ($field['type'] === 'File') {
                continue;
            }

            if ($field['type'] === 'number') {
                $casts[] = "\n            '{$field['name']}' => 'integer'";
            } elseif ($field['type'] === 'boolean') {
                $casts[] = "\n            '{$field['name']}' => 'boolean'";
            } elseif ($field['type'] === 'Date') {
                $casts[] = "\n            '{$field['name']}' => 'datetime'";
            }
        }

        return implode(',', $casts);
    }

    protected function getMigrationFields(): string
    {
        $fields = '';

        if ($this->identifierField === 'slug') {
            $fields .= "\n            \$table->string('slug')->unique();";
        }

        foreach ($this->fields as $field) {
            if ($field['type'] === 'File') {
                continue;
            }

            $type = $this->getMigrationType($field['type']);
            $nullable = ! $field['required'] ? '->nullable()' : '';
            $fields .= "\n            \$table->{$type}('{$field['name']}'){$nullable};";
        }

        return $fields;
    }

    protected function getMigrationType(string $type): string
    {
        return match ($type) {
            'string', 'email', 'password' => 'string',
            'number' => 'integer',
            'boolean' => 'boolean',
            'Date' => 'timestamp',
            'textarea' => 'text',
            'quill-editor' => 'longText',
            default => 'string',
        };
    }

    protected function getFactoryDefinition(): string
    {
        $definitions = [];

        foreach ($this->fields as $field) {
            if ($field['type'] === 'File') {
                continue;
            }

            $fakerMethod = $this->getFakerMethod($field['type'], $field['name']);
            $definitions[] = "            '{$field['name']}' => {$fakerMethod}";
        }

        return implode(",\n", $definitions);
    }

    protected function getFakerMethod(string $type, string $fieldName): string
    {
        $lower = strtolower($fieldName);

        return match ($type) {
            'string' => match (true) {
                str_contains($lower, 'email') => 'fake()->unique()->safeEmail()',
                str_contains($lower, 'phone') => 'fake()->phoneNumber()',
                str_contains($lower, 'name') => 'fake()->words(3, true)',
                default => 'fake()->sentence()',
            },
            'number' => 'fake()->numberBetween(1, 1000)',
            'boolean' => 'fake()->boolean()',
            'Date' => 'fake()->dateTimeBetween(\'-1 year\', \'now\')',
            'textarea' => 'fake()->paragraph(5)',
            'email' => 'fake()->unique()->safeEmail()',
            'password' => 'bcrypt(\'password\')',
            default => 'fake()->sentence()',
        };
    }

    protected function generateResource(): string
    {
        $fields = $this->getResourceFields();

        $content = "<?php

declare(strict_types=1);

namespace App\\Http\\Resources;

use Illuminate\\Http\\Resources\\Json\\JsonResource;

class {$this->studlySingular}Resource extends JsonResource
{
    public function toArray(\$request): array
    {
        return [
            'id' => \$this->id,{$fields}
            'created_at' => \$this->created_at,
            'updated_at' => \$this->updated_at,
        ];
    }
}
";

        $path = app_path("Http/Resources/{$this->studlySingular}Resource.php");
        File::put($path, $content);

        return $path;
    }

    protected function getResourceFields(): string
    {
        $fields = '';

        if ($this->identifierField === 'slug') {
            $fields .= "\n            'slug' => \$this->slug,";
        }

        foreach ($this->fields as $field) {
            if ($field['type'] === 'File') {
                continue;
            }
            $fields .= "\n            '{$field['name']}' => \$this->{$field['name']},";
        }

        if ($this->hasFileField()) {
            $collectionName = Str::snake($this->moduleName);
            $fields .= "\n            'media' => \$this->getMedia('{$collectionName}')->map(function (\$media) {
                return [
                    'id' => \$media->id,
                    'name' => \$media->name,
                    'file_name' => \$media->file_name,
                    'url' => \$media->getUrl(),
                ];
            }),";
        }

        return $fields;
    }

    protected function generateCollection(): string
    {
        $content = "<?php

declare(strict_types=1);

namespace App\\Http\\Resources\\Collections;

use App\\Http\\Resources\\{$this->studlySingular}Resource;
use Illuminate\\Http\\Resources\\Json\\ResourceCollection;

class {$this->studlySingular}Collection extends ResourceCollection
{
    public function toArray(\$request): array
    {
        return {$this->studlySingular}Resource::collection(\$this->collection)->toArray(\$request);
    }
}
";

        $path = app_path("Http/Resources/Collections/{$this->studlySingular}Collection.php");
        File::ensureDirectoryExists(dirname($path));
        File::put($path, $content);

        return $path;
    }

    protected function generateRequest(): string
    {
        $storeRules = $this->getValidationRules(true);
        $updateRules = $this->getValidationRules(false);

        $content = "<?php

declare(strict_types=1);

namespace App\\Http\\Requests;

use Orion\\Http\\Requests\\Request;

class {$this->studlySingular}Request extends Request
{
    public function storeRules(): array
    {
        return [{$storeRules}
        ];
    }

    public function updateRules(): array
    {
        return [{$updateRules}
        ];
    }

    public function commonMessages(): array
    {
        return [
            'required' => 'The :attribute field is required.',
            'string' => 'The :attribute field must be a string.',
            'numeric' => 'The :attribute field must be a number.',
            'boolean' => 'The :attribute field must be true or false.',
            'date' => 'The :attribute field must be a valid date.',
        ];
    }
}
";

        $path = app_path("Http/Requests/{$this->studlySingular}Request.php");
        File::put($path, $content);

        return $path;
    }

    protected function getValidationRules(bool $isStore): string
    {
        $rules = '';

        foreach ($this->fields as $field) {
            $rule = $this->getFieldValidation($field, $isStore);
            $rules .= "\n            '{$field['name']}' => {$rule},";
        }

        return $rules;
    }

    protected function getFieldValidation(array $field, bool $isStore): string
    {
        $rules = [];

        if ($field['required'] && $isStore) {
            $rules[] = "'required'";
        } elseif (! $isStore) {
            $rules[] = "'sometimes'";
        } else {
            $rules[] = "'nullable'";
        }

        $rules[] = match ($field['type']) {
            'string' => "'string', 'max:255'",
            'number' => "'numeric'",
            'boolean' => "'boolean'",
            'Date' => "'date'",
            'File' => "'array'",
            default => "'string'",
        };

        return '['.implode(', ', $rules).']';
    }

    protected function generatePolicy(): string
    {
        $content = "<?php

declare(strict_types=1);

namespace App\\Policies;

use App\\Models\\{$this->studlySingular};
use App\\Models\\User;

class {$this->studlySingular}Policy
{
    public function viewAny(?User \$user): bool
    {
        return true;
    }

    public function view(?User \$user, {$this->studlySingular} \${$this->singularName}): bool
    {
        return true;
    }

    public function create(?User \$user): bool
    {
        return true;
    }

    public function update(?User \$user, {$this->studlySingular} \${$this->singularName}): bool
    {
        return true;
    }

    public function delete(?User \$user, {$this->studlySingular} \${$this->singularName}): bool
    {
        return true;
    }

    public function restore(?User \$user, {$this->studlySingular} \${$this->singularName}): bool
    {
        return true;
    }

    public function forceDelete(?User \$user, {$this->studlySingular} \${$this->singularName}): bool
    {
        return true;
    }
}
";

        $path = app_path("Policies/{$this->studlySingular}Policy.php");
        File::put($path, $content);

        return $path;
    }

    protected function generateSeeder(): string
    {
        $content = "<?php

declare(strict_types=1);

namespace Database\\Seeders;

use App\\Models\\{$this->studlySingular};
use Illuminate\\Database\\Seeder;

class {$this->studlySingular}Seeder extends Seeder
{
    public function run(): void
    {
        {$this->studlySingular}::factory()->count(10)->create();
    }
}
";

        $path = database_path("seeders/{$this->studlySingular}Seeder.php");
        File::put($path, $content);

        return $path;
    }

    protected function addRoute(): string
    {
        $routesPath = base_path('routes/api.php');

        if (! File::exists($routesPath)) {
            return 'routes/api.php not found';
        }

        $content = File::get($routesPath);

        $routeLine = "    Orion::resource('{$this->moduleName}', {$this->studlySingular}Controller::class);";
        $importLine = "use App\\Http\\Controllers\\{$this->studlySingular}Controller;";

        // Add import if not exists
        if (! str_contains($content, $importLine)) {
            $content = preg_replace(
                '/(use\s+App\\\\Http\\\\Controllers\\\\[^;]+;)/',
                "$1\n{$importLine}",
                $content,
                1
            );
        }

        // Add route if not exists
        if (! str_contains($content, "Orion::resource('{$this->moduleName}'")) {
            $content = rtrim($content)."\n\n{$routeLine}\n";
        }

        File::put($routesPath, $content);

        return $routesPath;
    }

    protected function runMigration(): array
    {
        try {
            Artisan::call('migrate', ['--force' => true]);
            $output = Artisan::output();

            return [
                'success' => true,
                'message' => 'Migration executed successfully',
                'output' => $output,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Migration failed: '.$e->getMessage(),
            ];
        }
    }

    protected function runSeeder(): array
    {
        try {
            Artisan::call('db:seed', [
                '--class' => "Database\\Seeders\\{$this->studlySingular}Seeder",
                '--force' => true,
            ]);
            $output = Artisan::output();

            return [
                'success' => true,
                'message' => 'Seeder executed successfully',
                'output' => $output,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Seeder failed: '.$e->getMessage(),
            ];
        }
    }
}
