<?php

declare(strict_types=1);

namespace Laravel\Boost\Mcp\Tools;

use Laravel\Boost\Services\ModuleGeneratorService;
use Laravel\Mcp\Context;
use Laravel\Mcp\Resource;
use Laravel\Mcp\Response;
use Laravel\Mcp\Tool;

class DeleteModule extends Tool
{
    public function name(): string
    {
        return 'delete-module';
    }

    public function description(): string
    {
        return "Supprime un module complet (Modèle, Migration, Contrôleur, etc.) et ses configurations associées.";
    }

    public function schema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'module_name' => [
                    'type' => 'string',
                    'description' => 'Le nom du module à supprimer (ex: Articles, Products)',
                ],
            ],
            'required' => ['module_name'],
        ];
    }

    public function handle(Context $context, array $arguments): Response
    {
        $moduleName = $arguments['module_name'];

        /** @var ModuleGeneratorService $service */
        $service = app(ModuleGeneratorService::class, [
            'moduleName' => $moduleName,
            'fields' => [], // Fields are not needed for deletion
            'dryRun' => true, // Always dry-run for remote orchestrator
        ]);

        $result = $service->delete();

        if (! $result['success']) {
            return Response::error($result['message']);
        }

        return Response::json($result);
    }
}
