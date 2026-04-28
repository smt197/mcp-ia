<?php

declare(strict_types=1);

namespace Laravel\Boost\Mcp\Tools;

use DOMDocument;
use DOMXPath;
use Exception;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Support\Facades\Http;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class ScrapeWebsite extends Tool
{
    /**
     * The tool's description.
     */
    protected string $description = 'Scrape data from any website. Returns extracted text. Use selectors to limit data and avoid context overflow.';

    /**
     * Get the tool's input schema.
     *
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'url' => $schema->string()
                ->description('The URL of the website to scrape.')
                ->required(),
            'selectors' => $schema->array()
                ->items($schema->object())
                ->description('Array of objects with "name" and "xpath" (or "css") to extract multiple elements. Example: [{"name": "title", "xpath": "//h1"}]. If omitted, extracts the body text.'),
            'max_length' => $schema->integer()
                ->description('Maximum number of characters to return for the content. Defaults to 10,000.')
                ->default(10000),
        ];
    }

    /**
     * Handle the tool request.
     */
    public function handle(Request $request, Application $app): Response
    {
        $url = $request->get('url');
        $selectors = $request->get('selectors', []);
        $maxLength = $request->get('max_length', 10000);

        if (empty($url) || ! filter_var($url, FILTER_VALIDATE_URL)) {
            return Response::error('A valid URL is required.');
        }

        try {
            $response = Http::timeout(30)->get($url);

            if ($response->failed()) {
                return Response::error('Failed to fetch URL. Status code: '.$response->status());
            }

            $html = $response->body();

            if (empty($html)) {
                return Response::error('The response body is empty.');
            }

            // Suppress warnings for malformed HTML
            libxml_use_internal_errors(true);
            $dom = new DOMDocument;
            $dom->loadHTML($html, LIBXML_NOBLANKS | LIBXML_NOERROR);
            libxml_clear_errors();

            $xpath = new DOMXPath($dom);
            $result = [];

            if (empty($selectors)) {
                // Return all text if no selectors provided
                $bodyNode = $dom->getElementsByTagName('body')->item(0);
                $content = $bodyNode ? trim(preg_replace('/\s+/', ' ', $bodyNode->nodeValue ?? '')) : '';

                if (strlen($content) > $maxLength) {
                    $content = mb_substr($content, 0, $maxLength).'... [truncated]';
                }

                $result['content'] = $content;
            } else {
                foreach ($selectors as $selector) {
                    $name = $selector['name'] ?? null;
                    $xpathQuery = $selector['xpath'] ?? null;

                    if ($name && $xpathQuery) {
                        $elements = $xpath->query($xpathQuery);
                        $extracted = [];

                        if ($elements !== false) {
                            foreach ($elements as $element) {
                                $extracted[] = trim(preg_replace('/\s+/', ' ', $element->nodeValue ?? ''));
                            }
                        }

                        $result[$name] = count($extracted) === 1 ? $extracted[0] : $extracted;
                    }
                }
            }

            return Response::json([
                'url' => $url,
                'data' => $result,
            ]);
        } catch (Exception $e) {
            return Response::error('Scraping failed: '.$e->getMessage());
        }
    }
}
