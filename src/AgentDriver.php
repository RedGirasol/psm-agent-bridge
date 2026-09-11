<?php

declare(strict_types=1);

namespace AgentBridge;

interface AgentDriver
{
    /**
     * `$onEvent`, si se pasa, se llama con cada evento apenas el CLI lo emite
     * — antes de que la corrida termine. Sin esto, un CLI que se cuelga
     * preguntando algo no deja rastro hasta que se mata el proceso.
     *
     * `$onPid`, si se pasa, se llama una vez con el PID del proceso del CLI
     * apenas arranca (o `null` si el driver no expone uno). Sin esto no hay
     * forma de cancelar una corrida colgada desde fuera del job.
     *
     * @param (callable(array<string, mixed>): void)|null $onEvent
     * @param (callable(?int): void)|null $onPid
     */
    public function run(AgentRequest $request, ?callable $onEvent = null, ?callable $onPid = null): AgentResponse;
}
