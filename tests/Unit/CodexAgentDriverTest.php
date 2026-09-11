<?php

declare(strict_types=1);

namespace AgentBridge\Tests\Unit;

use AgentBridge\AgentRequest;
use AgentBridge\Drivers\CodexAgentDriver;
use CodexAgent\Client;
use CodexAgent\Options;
use CodexAgent\Transport\FakeTransport;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(CodexAgentDriver::class)]
final class CodexAgentDriverTest extends TestCase
{
    public function test_it_wraps_the_codex_sdk_response_into_agent_response(): void
    {
        $driver = new CodexAgentDriver(function (AgentRequest $request): Client {
            $transport = new FakeTransport([
                ['type' => 'thread.started', 'thread_id' => 'codex-session'],
                ['type' => 'item.completed', 'item' => ['type' => 'agent_message', 'text' => 'hola']],
                [
                    'type' => 'turn.completed',
                    'usage' => ['input_tokens' => 10, 'output_tokens' => 5],
                ],
            ]);

            return new Client(new Options(model: $request->model), $transport);
        });

        $response = $driver->run(new AgentRequest('hola', model: 'gpt-5'));

        $this->assertTrue($response->ok);
        $this->assertSame('turn.completed', $response->payload['type']);
        $this->assertCount(3, $response->transcript);
        $this->assertSame('codex-session', $response->sessionId);
        $this->assertNull($response->costUsd);
    }

    public function test_it_reads_available_models_from_the_cli_cache(): void
    {
        $home = sys_get_temp_dir().'/codex-driver-test-'.uniqid();
        mkdir($home, 0777, true);
        file_put_contents($home.'/models_cache.json', json_encode([
            'models' => [
                ['slug' => 'gpt-5.6-sol', 'display_name' => 'GPT-5.6-Sol', 'description' => 'Workhorse.'],
            ],
        ]));

        putenv('CODEX_HOME='.$home);

        try {
            $driver = new CodexAgentDriver();
            $models = $driver->availableModels();
        } finally {
            putenv('CODEX_HOME');
            @unlink($home.'/models_cache.json');
            @rmdir($home);
        }

        $this->assertSame([['slug' => 'gpt-5.6-sol', 'display_name' => 'GPT-5.6-Sol', 'description' => 'Workhorse.']], $models);
    }
}
