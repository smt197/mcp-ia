<?php

declare(strict_types=1);

namespace Laravel\Boost\Mcp\Tools;

use Exception;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Boost\Services\ModuleGeneratorService;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class GenerateModule extends Tool
{
    /**
     * The tool's description.
     */
    protected string $description = 'Generate a complete Laravel module with model, migration, controller, resources, policy, factory, seeder, and routes. Supports Orion REST API, Spatie Sluggable, and Spatie Media Library for file uploads. This tool creates production-ready CRUD modules following Laravel best practices.';

    /**
     * Get the tool's input schema.
     *
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'module_name' => $schema->string()
                ->description('Module name in plural form (e.g., "products", "blog_posts"). This will be used for the table name and routes.')
                ->required(),
            'fields' => $schema->array()
                ->items(

                    $schema->object([
                        'name' => $schema->string()->description('Field name (e.g., "title", "price", "description")')->required(),
                        'type' => $schema->string()
                            ->enum(['string', 'number', 'boolean', 'Date', 'File', 'textarea', 'quill-editor', 'email', 'password'])
                            ->description('Field type. Use "File" for file uploads (requires Spatie Media Library).')
                            ->required(),
                        'required' => $schema->boolean()->description('Whether the field is required')->required(),
                    ])
                )
                )
                ->description('Array of field definitions for the module')
                ->required(),
            'identifier_field' => $schema->string()
                ->enum(['id', 'slug'])
                ->description('Primary identifier field. Use "slug" for SEO-friendly URLs (requires Spatie Sluggable). Defaults to "id".'),
            'roles' => $schema->array()
                ->items($schema->string()->description('Role name'))
                ->description('Array of roles that can access this module. Defaults to ["user"].'),
        ];
    }

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        // Check if module generator is enabled
        if (! config('boost.module_generator.enabled', true)) {
            return Response::error('Module generator is disabled. Enable it in config/boost.php');
        }

        // Validate required parameters
        $moduleName = $request->get('module_name');
        $fields = $request->get('fields');

        if (empty($moduleName)) {
            return Response::error('module_name is required');
        }

        if (empty($fields) || ! is_array($fields)) {
            return Response::error('fields must be a non-empty array');
        }

        // Validate fields structure
        foreach ($fields as $index => $field) {
            if (! isset($field['name']) || ! isset($field['type']) || ! isset($field['required'])) {
                return Response::error("Field at index {$index} is missing required properties (name, type, required)");
            }

            $validTypes = ['string', 'number', 'boolean', 'Date', 'File', 'textarea', 'quill-editor', 'email', 'password'];

            if (! in_array($field['type'], $validTypes, true)) {
                return Response::error("Invalid field type '{$field['type']}' for field '{$field['name']}'. Valid types: ".implode(', ', $validTypes));
            }
        }

        // Get optional parameters
        $identifierField = $request->get('identifier_field', config('boost.module_generator.default_identifier', 'id'));
        $roles = $request->get('roles', config('boost.module_generator.default_roles', ['user']));

        // Validate identifier field
        if (! in_array($identifierField, ['id', 'slug'], true)) {
            return Response::error("Invalid identifier_field '{$identifierField}'. Must be 'id' or 'slug'.");
        }

        try {
            // Create the module generator service
            $generator = new ModuleGeneratorService(
                $moduleName,
                $fields,
                $identifierField,
                $roles
            );

            // Generate the module
            $result = $generator->generate();

            if (! $result['success']) {
                return Response::error($result['message']."\n\nError details:\n".$result['error'] ?? 'No additional details');
            }

            // Format success response
            $filesGenerated = [];

            foreach ($result['files'] as $key => $path) {
                if (is_string($path)) {
                    $filesGenerated[] = "- {$key}: {$path}";
                } elseif (is_array($path) && isset($path['success'])) {
                    $status = $path['success'] ? '✓' : '✗';
                    $filesGenerated[] = "- {$key}: {$status} {$path['message']}";
                }
            }

            $responseText = "✅ Module '{$moduleName}' generated successfully!\n\n";
            $responseText .= "Files created:\n".implode("\n", $filesGenerated)."\n\n";
            $responseText .= "Next steps:\n";
            $responseText .= "1. Review the generated files\n";
            $responseText .= "2. Customize the controller, resource, or policy as needed\n";
            $responseText .= "3. Test the API endpoints at /api/{$moduleName}\n";

            if (config('boost.module_generator.auto_migrate', true)) {
                $responseText .= "\n✓ Migration was automatically executed";
            } else {
                $responseText .= "\n⚠ Run 'php artisan migrate' to create the database table";
            }

            if (config('boost.module_generator.auto_seed', true)) {
                $responseText .= "\n✓ Seeder was automatically executed (10 sample records created)";
            } else {
                $responseText .= "\n⚠ Run 'php artisan db:seed --class={$result['files']['seeder']}' to seed sample data";
            }

            return Response::text($responseText);
        } catch (Exception $e) {
            return Response::error('Module generation failed: '.$e->getMessage()."\n\nStack trace:\n".$e->getTraceAsString());
        }
    }
}
