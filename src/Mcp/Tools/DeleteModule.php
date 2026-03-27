<?php

declare(strict_types=1);

namespace Laravel\Boost\Mcp\Tools;

use Exception;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Boost\Services\ModuleGeneratorService;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class DeleteModule extends Tool
{
    /**
     * The tool's description.
     */
    protected string $description = 'Supprime un module complet (Modèle, Migration, Contrôleur, etc.) et ses configurations associées.';

    /**
     * Get the tool's input schema.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'module_name' => $schema->string()
                ->description('Le nom du module à supprimer (ex: Articles, Products)')
                ->required(),
        ];
    }

    /**
     * Handle the tool request.
     */
    public function handle(Request $request, Application $app): Response
    {
        $moduleName = $request->get('module_name');

        if (empty($moduleName)) {
            return Response::error('module_name is required');
        }

        try {
            /** @var ModuleGeneratorService $service */
            $service = new ModuleGeneratorService(
                $app,
                $moduleName,
                fields: [], // Non requis pour la suppression
                identifierField: 'id',
                roles: [],
                dryRun: true // Toujours dry-run pour l'orchestrateur distant
            );

            $result = $service->delete();

            if (! $result['success']) {
                return Response::error($result['message']);
            }

            // Retourner le JSON structuré pour l'orchestrateur
            return Response::text(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        } catch (Exception $e) {
            return Response::error('Module deletion failed: '.$e->getMessage());
        }
    }
}
