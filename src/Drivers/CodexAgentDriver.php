<?php

declare(strict_types=1);

namespace AgentBridge\Drivers;

use AgentBridge\AgentDriver;
use AgentBridge\AgentRequest;
use AgentBridge\AgentResponse;
use CodexAgent\Client;
use CodexAgent\Options;

class CodexAgentDriver implements AgentDriver
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
            cwd: $request->cwd,
            addDirs: $request->addDirs,
            env: $request->env,
            sandbox: $this->sandboxFor($request),
            // `never`: el CLI corre sin terminal, así que nunca hay quien
            // conteste una aprobación. Sin esto se cuelga esperando input.
            approvalPolicy: 'never',
        )) )($request);

        $events = [];

        // El pid se captura apenas arranca el proceso, no en el primer
        // evento: si el CLI se cuelga antes de emitir nada (como con el bug
        // de stdin sin EOF), nunca hay "primer evento" y el pid se perdería.
        if (method_exists($client, 'send') && method_exists($client, 'receive')) {
            $client->send($request->prompt);
            $onPid?->__invoke(method_exists($client, 'pid') ? $client->pid() : null);

            foreach ($client->receive() as $event) {
                $events[] = $event;
                $onEvent?->__invoke($event);
            }
        } else {
            foreach ($client->query($request->prompt) as $event) {
                $events[] = $event;
                $onEvent?->__invoke($event);
            }
        }

        return AgentResponse::fromEvents($events);
    }

    private function sandboxFor(AgentRequest $request): string
    {
        $disallowed = $request->disallowedTools ?? [];

        return in_array('Write', $disallowed, true) && in_array('Edit', $disallowed, true)
            ? 'read-only'
            : 'workspace-write';
    }
}
