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
        $client = ($this->clientFactory ?? fn (AgentRequest $request): object => new Client(new Options(
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
            settings: $request->settings,
        )) )($request);

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
     * El SDK de Claude no expone un catálogo de modelos: no hay de dónde
     * leerlo, a diferencia de `models_cache.json` en Codex.
     *
     * @return list<array{slug: string, display_name: string, description: string}>
     */
    public function availableModels(): array
    {
        return [];
    }
}
