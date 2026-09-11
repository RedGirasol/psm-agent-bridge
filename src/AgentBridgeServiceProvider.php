<?php

declare(strict_types=1);

namespace AgentBridge;

use Illuminate\Support\ServiceProvider;

class AgentBridgeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/agent-bridge.php', 'agent-bridge');

        $this->app->singleton('agent-bridge', function ($app) {
            return new AgentManager($app);
        });

        $this->app->bind(AgentDriver::class, function ($app) {
            return $app['agent-bridge']->driver();
        });
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/agent-bridge.php' => config_path('agent-bridge.php'),
        ], 'agent-bridge-config');
    }
}
