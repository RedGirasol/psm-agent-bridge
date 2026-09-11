<?php

declare(strict_types=1);

namespace AgentBridge\Facades;

use Illuminate\Support\Facades\Facade;

class Agent extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'agent-bridge';
    }
}
