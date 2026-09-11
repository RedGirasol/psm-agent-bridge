<?php

declare(strict_types=1);

namespace AgentBridge;

use AgentBridge\Drivers\ClaudeAgentDriver;
use AgentBridge\Drivers\CodexAgentDriver;
use Illuminate\Support\Manager;

class AgentManager extends Manager
{
    public function getDefaultDriver(): string
    {
        return $this->config->get('agent-bridge.driver', 'fake');
    }

    protected function createFakeDriver(): \AgentBridge\AgentDriver
    {
        return new class implements \AgentBridge\AgentDriver {
            public function run(\AgentBridge\AgentRequest $request, ?callable $onEvent = null, ?callable $onPid = null): \AgentBridge\AgentResponse
            {
                return new \AgentBridge\AgentResponse([
                    'type' => 'result',
                    'status' => 'ok',
                    'prompt' => $request->prompt,
                    'model' => $request->model,
                    'stream' => $request->stream,
                ]);
            }

            public function availableModels(): array
            {
                return [];
            }
        };
    }

    protected function createClaudeDriver(): \AgentBridge\AgentDriver
    {
        return new ClaudeAgentDriver();
    }

    protected function createCodexDriver(): \AgentBridge\AgentDriver
    {
        return new CodexAgentDriver();
    }
}
