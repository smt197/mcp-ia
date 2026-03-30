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
    protected string $description = 'Modifie un module existant. Tu DOIS fournir la liste complète des champs ET détailler les changements dans l\'objet "changes" pour générer la migration.';

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
                'added' => $schema->array()
                    ->items($schema->string()->description('Format "name:type:required|nullable"'))
                    ->description('OBLIGATOIRE si ajout : Liste des NOUVEAUX champs à ajouter (ex: ["prix:number:required"]).'),
                'renamed' => $schema->array()
                    ->items($schema->object([
                        'old' => $schema->string()->description('Ancien nom'),
                        'new' => $schema->string()->description('Nouveau nom'),
                    ]))
                    ->description('Facultatif : Liste des colonnes à renommer.'),
                'modified' => $schema->array()
                    ->items($schema->string()->description('Format "name:type:required|nullable"'))
                    ->description('Facultatif : Colonnes dont le type change.'),
            ])->description('OBJET REQUIS pour la migration. Ne pas envoyer de texte ici, seulement l\'objet structuré.')
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

        // Si l'IA a envoyé une chaîne au lieu d'un objet pour changes
        if (is_string($changesRaw)) {
            return Response::error('Le paramètre "changes" doit être un OBJET JSON avec une clé "added", "renamed" ou "modified", pas une simple phrase.');
        }

        // Parser la liste complète des champs
        $allFields = $this->parseFields($fieldsStrings);
        
        // Parser les changements structurés
        $changes = [];
        if (isset($changesRaw['added']) && is_array($changesRaw['added'])) {
            $changes['added'] = $this->parseFields($changesRaw['added']);
        }
        if (isset($changesRaw['renamed']) && is_array($changesRaw['renamed'])) {
            $changes['renamed'] = $changesRaw['renamed'];
        }
        if (isset($changesRaw['modified']) && is_array($changesRaw['modified'])) {
            $changes['modified'] = $this->parseFields($changesRaw['modified']);
        }

        try {
            /** @var ModuleGeneratorService $service */
            $service = new ModuleGeneratorService(
                $app,
                $moduleName,
                fields: [], 
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

    private function parseFields(array $fieldsInputs): array
    {
        $fields = [];
        foreach ($fieldsInputs as $input) {
            // Si l'IA envoie un objet au lieu d'une chaîne (comportement fréquent des modèles récents)
            if (is_array($input) || is_object($input)) {
                $input = (array) $input;
                $fields[] = [
                    'name' => $input['name'] ?? ($input['field'] ?? ''),
                    'type' => $input['type'] ?? 'string',
                    'required' => (bool) ($input['required'] ?? (! ($input['nullable'] ?? true))),
                ];
                continue;
            }

            // Sinon on traite comme une chaîne "name:type:required"
            $parts = explode(':', (string) $input, 3);
            if (count($parts) < 2) {
                continue;
            }

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
