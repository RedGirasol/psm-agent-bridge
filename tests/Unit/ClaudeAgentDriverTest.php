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

    public function test_it_hands_mcp_servers_and_the_cli_path_to_the_sdk(): void
    {
        $servers = ['pms' => ['type' => 'stdio', 'command' => 'php', 'args' => ['artisan', 'mcp:start', 'pms-agent']]];

        $options = (new ClaudeAgentDriver())->options(new AgentRequest(
            'hola',
            allowedTools: ['Read', 'mcp__pms'],
            mcpServers: $servers,
            cliPath: '/opt/app/bin/claude-wrapper',
        ));

        $this->assertSame($servers, $options->mcpServers);
        $this->assertSame('/opt/app/bin/claude-wrapper', $options->cliPath);

        $args = $options->toCliArgs();
        $config = $args[array_search('--mcp-config', $args, true) + 1] ?? null;
        $this->assertSame(['mcpServers' => $servers], json_decode((string) $config, true));
    }

    public function test_without_mcp_servers_no_mcp_config_is_sent(): void
    {
        $options = (new ClaudeAgentDriver())->options(new AgentRequest('hola'));

        $this->assertSame([], $options->mcpServers);
        $this->assertNull($options->cliPath);
        $this->assertNotContains('--mcp-config', $options->toCliArgs());
    }

    public function test_it_lists_the_known_verified_models(): void
    {
        $driver = new ClaudeAgentDriver();

        $slugs = array_column($driver->availableModels(), 'slug');

        $this->assertSame(['claude-opus-5', 'claude-sonnet-5', 'claude-haiku-4-5'], $slugs);
    }
}
