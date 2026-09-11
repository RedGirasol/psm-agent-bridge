<?php

declare(strict_types=1);

namespace AgentBridge\Tests\Unit;

use AgentBridge\AgentRequest;
use AgentBridge\Drivers\ClaudeAgentDriver;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ClaudeAgentDriver::class)]
final class ClaudeAgentDriverTest extends TestCase
{
    public function test_it_wraps_the_claude_sdk_response_into_agent_response(): void
    {
        $driver = new ClaudeAgentDriver(function (AgentRequest $request): object {
            return new class {
                public function query(string $prompt): \Generator
                {
                    yield ['type' => 'assistant', 'message' => ['model' => 'claude-sonnet-4', 'content' => 'hola']];
                    yield [
                        'type' => 'result',
                        'status' => 'ok',
                        'session_id' => 'claude-session',
                        'total_cost_usd' => '0.12',
                    ];
                }
            };
        });

        $response = $driver->run(new AgentRequest('hola', model: 'claude-sonnet-4'));

        $this->assertTrue($response->ok);
        $this->assertSame('result', $response->payload['type']);
        $this->assertSame('ok', $response->payload['status']);
        $this->assertCount(2, $response->transcript);
        $this->assertSame('claude-session', $response->sessionId);
        $this->assertSame('0.12', $response->costUsd);
        $this->assertSame('claude-sonnet-4', $response->model);
    }
}
