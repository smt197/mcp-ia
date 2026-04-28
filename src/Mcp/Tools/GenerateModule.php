<?php

declare(strict_types=1);

namespace Laravel\Boost\Mcp\Tools;

use Exception;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Boost\Services\ModuleGeneratorService; // Importez l'application
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
                ->items($schema->string())
                ->description('Array of field definitions, each as a single string in "name:type:flag" format. The flag must be "required" or "nullable". Examples: ["name:string:required", "description:textarea:nullable", "price:number:required"]')
                ->required(),
            'identifier_field' => $schema->string()
                ->enum(['id', 'slug'])
                ->description('Primary identifier field. Use "slug" for SEO-friendly URLs (requires Spatie Sluggable). Defaults to "id".'),
            'roles' => $schema->array()
                ->items($schema->string()->description('Role name'))
                ->description('Array of roles that can access this module. Defaults to ["user"].'),
            'initial_data' => $schema->array()
                ->items($schema->object())
                ->description('Optional array of initial data objects to be inserted by the Seeder. Example: [{"title": "Hello", "content": "World"}]'),
        ];
    }

    /**
     * Handle the tool request.
     */
    public function handle(Request $request, Application $app): Response
    {
        // Check if module generator is enabled
        if (! config('boost.module_generator.enabled', true)) {
            return Response::error('Module generator is disabled. Enable it in config/boost.php');
        }

        // Validate required parameters
        $moduleName = $request->get('module_name');
        $fieldsStrings = $request->get('fields');

        if (empty($moduleName)) {
            return Response::error('module_name is required');
        }

        if (empty($fieldsStrings) || ! is_array($fieldsStrings)) {
            return Response::error('fields must be a non-empty array');
        }

        $fields = [];

        foreach ($fieldsStrings as $fieldString) {
            $parts = explode(':', $fieldString, 3);

            if (count($parts) !== 3) {
                return Response::error("Invalid field format: '{$fieldString}'. Must be in 'name:type:required|nullable' format.");
            }

            $flag = strtolower($parts[2]);

            if (! in_array($flag, ['required', 'nullable'], true)) {
                return Response::error("Invalid flag '{$parts[2]}' in '{$fieldString}'. Must be 'required' or 'nullable'.");
            }

            $fields[] = [
                'name' => $parts[0],
                'type' => $parts[1],
                'required' => ($flag === 'required'),
            ];
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
        $initialData = $request->get('initial_data');

        // Validate identifier field
        if (! in_array($identifierField, ['id', 'slug'], true)) {
            return Response::error("Invalid identifier_field '{$identifierField}'. Must be 'id' or 'slug'.");
        }

        try {
            // Always use dryRun mode: generate file contents without writing to disk
            // The client-package will write the files to its own project
            $generator = new ModuleGeneratorService(
                $app,
                $moduleName,
                $fields,
                $identifierField,
                $roles,
                dryRun: true,
                initialData: $initialData
            );

            $result = $generator->generate();

            if (! $result['success']) {
                return Response::error($result['message']."\n\nError details:\n".$result['error'] ?? 'No additional details');
            }

            // Return the structured JSON with file contents for client-package to write
            return Response::text(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        } catch (Exception $e) {
            return Response::error('Module generation failed: '.$e->getMessage()."\n\nStack trace:\n".$e->getTraceAsString());
        }
    }
}
