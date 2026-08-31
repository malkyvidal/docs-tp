<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Prism\Prism\Schema\RawSchema;
use Prism\Prism\Tool as PrismTool;

class McpClient
{
    private int $requestId = 0;

    private bool $initialized = false;

    /**
     * @var array<string, array{description: string, inputSchema: array<string, mixed>}>
     */
    private array $tools = [];

    public function __construct(
        private readonly string $url = 'http://localhost:3001/mcp',
    ) {}

    /**
     * Initialize the MCP session with the server.
     */
    public function initialize(): void
    {
        if ($this->initialized) {
            return;
        }

        $response = $this->sendRequest('initialize', [
            'protocolVersion' => '2024-11-05',
            'capabilities' => new \stdClass(),
            'clientInfo' => [
                'name' => 'signadocs-chat',
                'version' => '1.0.0',
            ],
        ]);

        if ($response === null) {
            throw new \RuntimeException('No se pudo inicializar la conexión con el servidor MCP.');
        }

        $this->initialized = true;
    }

    /**
     * Discover available tools from the MCP server.
     *
     * @return array<string, array{description: string, inputSchema: array<string, mixed>}>
     */
    public function listTools(): array
    {
        $this->initialize();

        if ($this->tools !== []) {
            return $this->tools;
        }

        $response = $this->sendRequest('tools/list');

        if ($response === null || ! isset($response['tools'])) {
            return [];
        }

        foreach ($response['tools'] as $tool) {
            $this->tools[$tool['name']] = [
                'description' => $tool['description'] ?? '',
                'inputSchema' => $tool['inputSchema'] ?? ['type' => 'object', 'properties' => []],
            ];
        }

        return $this->tools;
    }

    /**
     * Convert discovered MCP tools into Prism Tool instances.
     *
     * @return PrismTool[]
     */
    public function getPrismTools(): array
    {
        $tools = $this->listTools();

        return array_map(function (string $name, array $definition): PrismTool {
            $tool = (new PrismTool)
                ->as($name)
                ->for($definition['description']);

            $schema = $definition['inputSchema'];
            $properties = $schema['properties'] ?? [];
            $required = $schema['required'] ?? [];

            foreach ($properties as $paramName => $paramDef) {
                $isRequired = in_array($paramName, $required);
                $schema = [
                    'type' => $paramDef['type'] ?? 'string',
                    'description' => $paramDef['description'] ?? $paramName,
                ];
                if (! $isRequired) {
                    $schema['nullable'] = true;
                }

                $tool->withParameter(
                    new RawSchema($paramName, $schema),
                    $isRequired,
                );
            }

            return $tool->using(function (mixed ...$args) use ($name): string {
                /** @var array<string, mixed> $args */
                return $this->callTool($name, $args);
            });
        }, array_keys($tools), array_values($tools));
    }

    /**
     * Call an MCP tool by name and return the result as a string.
     *
     * @param array<string, mixed> $arguments
     */
    public function callTool(string $name, array $arguments = []): string
    {
        $this->initialize();

        $response = $this->sendRequest('tools/call', [
            'name' => $name,
            'arguments' => $arguments,
        ]);

        if ($response === null) {
            return 'Error: no se obtuvo respuesta del servidor MCP.';
        }

        if (isset($response['isError']) && $response['isError']) {
            $content = $response['content'] ?? [];
            $text = is_array($content) ? implode(', ', array_column($content, 'text')) : (string) $content;

            return 'Error del MCP: '.$text;
        }

        $content = $response['content'] ?? [];

        if (! is_array($content)) {
            return (string) $content;
        }

        $parts = [];
        foreach ($content as $item) {
            if (isset($item['text'])) {
                $parts[] = $item['text'];
            } elseif (isset($item['type']) && $item['type'] === 'text') {
                $parts[] = $item['text'] ?? '';
            }
        }

        return $parts !== [] ? implode("\n", $parts) : json_encode($response, JSON_THROW_ON_ERROR);
    }

    /**
     * Send a JSON-RPC request and return the result.
     *
     * @param array<string, mixed> $params
     * @return array<string, mixed>|null
     */
    private function sendRequest(string $method, array $params = []): ?array
    {
        $this->requestId++;

        $payload = [
            'jsonrpc' => '2.0',
            'id' => $this->requestId,
            'method' => $method,
        ];

        if ($params !== []) {
            $payload['params'] = $params;
        }

        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json, text/event-stream',
                ])
                ->post($this->url, $payload);

            if ($response->failed()) {
                Log::error('MCP request failed', [
                    'method' => $method,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return null;
            }

            $contentType = (string) $response->header('Content-Type');

            if (str_contains($contentType, 'text/event-stream')) {
                return $this->parseSseResponse($response->body());
            }

            $body = $response->json();

            if (isset($body['error'])) {
                Log::error('MCP JSON-RPC error', [
                    'method' => $method,
                    'error' => $body['error'],
                ]);

                return null;
            }

            return $body['result'] ?? null;
        } catch (\Exception $e) {
            Log::error('MCP connection error', [
                'method' => $method,
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Send a JSON-RPC notification (no response expected).
     *
     * @param array<string, mixed> $params
     */
    private function sendNotification(string $method, array $params = []): void
    {
        $payload = [
            'jsonrpc' => '2.0',
            'method' => $method,
            'params' => $params,
        ];

        try {
            Http::timeout(10)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json, text/event-stream',
                ])
                ->post($this->url, $payload);
        } catch (\Exception $e) {
            Log::warning('MCP notification failed', [
                'method' => $method,
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Parse an SSE-formatted response body to extract the JSON-RPC result.
     *
     * @return array<string, mixed>|null
     */
    private function parseSseResponse(string $body): ?array
    {
        $lines = explode("\n", $body);
        $dataLines = [];

        foreach ($lines as $line) {
            if (str_starts_with($line, 'data: ')) {
                $dataLines[] = substr($line, 6);
            }
        }

        if ($dataLines === []) {
            return null;
        }

        $lastData = end($dataLines);

        try {
            $decoded = json_decode($lastData, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            $concatenated = implode('', $dataLines);

            try {
                $decoded = json_decode($concatenated, true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException) {
                return null;
            }
        }

        if (isset($decoded['result'])) {
            return $decoded['result'];
        }

        if (isset($decoded['error'])) {
            return null;
        }

        return $decoded;
    }
}
