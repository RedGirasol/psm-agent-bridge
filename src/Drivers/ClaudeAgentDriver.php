<?php

declare(strict_types=1);

namespace AgentBridge\Drivers;

use AgentBridge\AgentDriver;
use AgentBridge\AgentRequest;
use AgentBridge\AgentResponse;
use ClaudeAgent\Client;
use ClaudeAgent\Options;

class ClaudeAgentDriver implements AgentDriver
{
    /**
     * @param (callable(AgentRequest): object)|null $clientFactory
     */
    public function __construct(
        private readonly mixed $clientFactory = null,
    ) {
    }

    public function run(AgentRequest $request, ?callable $onEvent = null, ?callable $onPid = null): AgentResponse
    {
        $client = ($this->clientFactory ?? fn (AgentRequest $request): object => new Client($this->options($request)))($request);

        $events = [];

        // El SDK de Claude no expone el PID del proceso: no hay `pid()` en su
        // Client, así que aquí no hay forma de cancelar por proceso.
        $onPid?->__invoke(method_exists($client, 'pid') ? $client->pid() : null);

        foreach ($client->query($request->prompt) as $event) {
            $events[] = $event;
            $onEvent?->__invoke($event);
        }

        return AgentResponse::fromEvents($events);
    }

    /**
     * Cómo se traduce una petición del bridge a las opciones del SDK de Claude.
     */
    public function options(AgentRequest $request): Options
    {
        return new Options(
            model: $request->model,
            systemPrompt: $request->systemPrompt,
            appendSystemPrompt: $request->appendSystemPrompt,
            allowedTools: $request->allowedTools,
            disallowedTools: $request->disallowedTools,
            permissionMode: $request->permissionMode,
            maxTurns: $request->maxTurns,
            maxBudgetUsd: $request->maxBudgetUsd,
            cwd: $request->cwd,
            addDirs: $request->addDirs,
            env: $request->env,
            thinking: $request->thinking,
            resume: $request->resume,
            mcpServers: $request->mcpServers,
            settings: $request->settings,
            cliPath: $request->cliPath,
        );
    }

    /**
     * El CLI de Claude no tiene un `models_cache.json` como Codex: no hay
     * archivo que leer. Esta lista salió de probar cada alias contra el CLI
     * real (`claude -p --model <alias> --output-format json`) y leer el
     * `canonicalModel` que devolvió para esta cuenta — no es una lista
     * inventada, pero tampoco se refresca sola: si Anthropic cambia los
     * nombres, hay que volver a correr esa prueba y actualizar esta lista.
     *
     * @return list<array{slug: string, display_name: string, description: string}>
     */
    public function availableModels(): array
    {
        return [
            [
                'slug' => 'claude-opus-5',
                'display_name' => 'Claude Opus 5',
                'description' => 'El más capaz; mayor costo y latencia.',
            ],
            [
                'slug' => 'claude-sonnet-5',
                'display_name' => 'Claude Sonnet 5',
                'description' => 'Balance entre capacidad y costo; el default habitual.',
            ],
            [
                'slug' => 'claude-haiku-4-5',
                'display_name' => 'Claude Haiku 4.5',
                'description' => 'El más rápido y económico; menos profundidad de razonamiento.',
            ],
        ];
    }
}
