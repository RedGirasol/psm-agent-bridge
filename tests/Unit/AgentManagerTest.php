<?php

declare(strict_types=1);

namespace AgentBridge\Tests\Unit;

use AgentBridge\AgentManager;
use AgentBridge\AgentRequest;
use Illuminate\Container\Container;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AgentManager::class)]
final class AgentManagerTest extends TestCase
{
    public function test_it_uses_the_fake_driver_by_default(): void
    {
        $container = new Container();
        $container->instance('config', new class {
            public function get(string $key, mixed $default = null): mixed
            {
                return $default;
            }
        });

        $manager = new AgentManager($container);
        $driver = $manager->driver();

        $response = $driver->run(new AgentRequest('hola', model: 'gpt-5'));

        $this->assertTrue($response->ok);
        $this->assertSame('result', $response->payload['type']);
        $this->assertSame('hola', $response->payload['prompt']);
    }
}
