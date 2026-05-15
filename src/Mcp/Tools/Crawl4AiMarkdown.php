<?php

declare(strict_types=1);

namespace Laravel\Boost\Mcp\Tools;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Support\Facades\Http;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;

#[Name('crawl4ai-markdown')]
class Crawl4AiMarkdown extends Tool
{
    protected string $description = 'Scrape a URL with the Crawl4AI container and return clean markdown.';

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'url' => $schema->string()
                ->description('Absolute http/https URL to scrape.')
                ->required(),
            'filter' => $schema->string()
                ->enum(['fit', 'raw', 'bm25', 'llm'])
                ->description('Crawl4AI markdown filter. Defaults to fit.'),
            'query' => $schema->string()
                ->description('Optional query for bm25 or llm filtering.'),
            'cache' => $schema->string()
                ->description('Use "1" to enable cache, "0" to bypass. Defaults to 0.'),
            'timeout' => $schema->integer()
                ->description('HTTP timeout in seconds. Defaults to 120.'),
        ];
    }

    public function handle(Request $request, Application $app): Response
    {
        $baseUrl = rtrim((string) config('services.crawl4ai.base_url', 'http://crawl4ai:11235'), '/');

        if (empty($baseUrl)) {
            return Response::error('CRAWL4AI_BASE_URL is not configured. Set it in your .env file.');
        }

        $timeout = (int) $request->get('timeout', 120);

        $response = Http::timeout($timeout)->post($baseUrl.'/md', [
            'url' => $request->get('url'),
            'f' => $request->get('filter', 'fit'),
            'q' => $request->get('query'),
            'c' => (string) $request->get('cache', '0'),
        ]);

        if ($response->failed()) {
            return Response::error('Crawl4AI failed with status '.$response->status().': '.$response->body());
        }

        return Response::json($response->json());
    }
}
