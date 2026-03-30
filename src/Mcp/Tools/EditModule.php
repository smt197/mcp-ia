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

class EditModule extends Tool
{
    /**
     * The tool's description.
     */
    protected string $description = 'Modifie un module existant : ajoute des champs, les renomme ou modifie leurs types. Génère une migration d\'altération et met à jour les fichiers (Model, Controller, etc.).';

    /**
     * Get the tool's input schema.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'module_name' => $schema->string()
                ->description('Le nom du module à modifier (ex: Articles)')
                ->required(),
            'fields' => $schema->array()
                ->items($schema->string()->description('Format "name:type:required|nullable"'))
                ->description('La liste COMPLÈTE de TOUS les champs souhaités pour le module après modification.')
                ->required(),
            'changes' => $schema->object([
                'added' => $schema->array()->items($schema->string()->description('Format "name:type:required|nullable"'))->description('Nouveaux champs à ajouter via une migration.'),
                'renamed' => $schema->array()->items($schema->object([
                    'old' => $schema->string()->description('Ancien nom du champ'),
                    'new' => $schema->string()->description('Nouveau nom du champ'),
                ]))->description('Champs à renommer.'),
                'modified' => $schema->array()->items($schema->string()->description('Format "name:type:required|nullable"'))->description('Champs dont le type ou la nullabilité a changé.'),
            ])->description('Détails des changements spécifiques pour la migration d\'altération.')
            ->required(),
        ];
    }

    /**
     * Handle the tool request.
     */
    public function handle(Request $request, Application $app): Response
    {
        $moduleName = $request->get('module_name');
        $fieldsStrings = $request->get('fields');
        $changesRaw = $request->get('changes', []);

        if (empty($moduleName) || empty($fieldsStrings)) {
            return Response::error('module_name and fields are required');
        }

        // Parser la liste complète des champs
        $allFields = $this->parseFields($fieldsStrings);
        
        // Parser les changements structurés
        $changes = [];
        if (isset($changesRaw['added'])) {
            $changes['added'] = $this->parseFields($changesRaw['added']);
        }
        if (isset($changesRaw['renamed'])) {
            $changes['renamed'] = $changesRaw['renamed'];
        }
        if (isset($changesRaw['modified'])) {
            $changes['modified'] = $this->parseFields($changesRaw['modified']);
        }

        try {
            /** @var ModuleGeneratorService $service */
            $service = new ModuleGeneratorService(
                $app,
                $moduleName,
                fields: [], // Sera écrasé par edit()
                identifierField: 'id',
                roles: [],
                dryRun: true
            );

            $result = $service->edit($allFields, $changes);

            if (! $result['success']) {
                return Response::error($result['message']);
            }

            return Response::text(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        } catch (Exception $e) {
            return Response::error('Module update failed: '.$e->getMessage());
        }
    }

    private function parseFields(array $fieldsStrings): array
    {
        $fields = [];
        foreach ($fieldsStrings as $fieldString) {
            $parts = explode(':', $fieldString, 3);
            if (count($parts) < 2) continue;
            
            $flag = strtolower($parts[2] ?? 'nullable');
            $fields[] = [
                'name' => $parts[0],
                'type' => $parts[1],
                'required' => ($flag === 'required'),
            ];
        }
        return $fields;
    }
}
