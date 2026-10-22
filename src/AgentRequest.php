<?php

declare(strict_types=1);

namespace AgentBridge;

final class AgentRequest
{
    /**
     * @param string $prompt
     * @param string|null $systemPrompt
     * @param string|null $cwd
     * @param list<string> $addDirs
     * @param array<string, string> $env
     * @param string|null $model
     * @param int|null $maxTurns
    * @param float|null $maxBudgetUsd
    * @param string|null $resume
    * @param string|null $permissionMode
    * @param list<string>|null $allowedTools
    * @param list<string>|null $disallowedTools
    * @param string|null $thinking
    * @param string|null $settings
    * @param string|null $appendSystemPrompt
     * @param bool $stream
     * @param array<string, array<string, mixed>> $mcpServers Servidores MCP de la corrida, con la forma de `--mcp-config`
     *        (`['pms' => ['type' => 'stdio', 'command' => ..., 'args' => [...]]]`). Por ahora solo el driver de Claude
     *        los entrega; el de Codex los ignora.
     * @param string|null $cliPath Binario del CLI a lanzar en lugar del que se encuentre en el PATH. Por ahora solo
     *        el driver de Claude lo usa.
     */
    public function __construct(
        public readonly string $prompt,
        public readonly ?string $systemPrompt = null,
        public readonly ?string $cwd = null,
        public readonly array $addDirs = [],
        public readonly array $env = [],
        public readonly ?string $model = null,
        public readonly ?int $maxTurns = null,
        public readonly ?float $maxBudgetUsd = null,
        public readonly ?string $resume = null,
        public readonly ?string $permissionMode = null,
        public readonly ?array $allowedTools = null,
        public readonly ?array $disallowedTools = null,
        public readonly ?string $thinking = null,
        public readonly ?string $settings = null,
        public readonly ?string $appendSystemPrompt = null,
        public readonly bool $stream = true,
        public readonly array $mcpServers = [],
        public readonly ?string $cliPath = null,
    ) {
    }
}
