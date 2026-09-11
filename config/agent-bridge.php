<?php

return [
    'driver' => env('AGENT_BRIDGE_DRIVER', 'fake'),

    'drivers' => [
        'fake' => [
            'driver' => 'fake',
        ],
        'claude' => [
            'driver' => 'claude',
        ],
        'codex' => [
            'driver' => 'codex',
        ],
    ],
];
