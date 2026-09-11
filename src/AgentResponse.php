<?php

declare(strict_types=1);

namespace AgentBridge;

final class AgentResponse
{
    /**
     * @param array<string, mixed> $payload
     * @param list<array<string, mixed>> $transcript
     */
    public function __construct(
        public readonly array $payload = [],
        public readonly bool $ok = true,
        public readonly ?string $error = null,
        public readonly array $transcript = [],
        public readonly ?string $sessionId = null,
        public readonly ?string $costUsd = null,
        public readonly ?string $subtype = null,
        public readonly ?string $model = null,
    ) {
    }

    /**
     * Normalize provider result events into the contract consumed by PSM.
     *
     * @param list<array<string, mixed>> $events
     */
    public static function fromEvents(array $events): self
    {
        $result = null;
        $model = null;

        foreach ($events as $event) {
            if (($event['type'] ?? null) === 'assistant') {
                $candidate = $event['message']['model'] ?? $event['model'] ?? null;
                $model ??= is_string($candidate) && $candidate !== '' ? $candidate : null;
            }

            if (in_array($event['type'] ?? null, ['result', 'turn.completed', 'turn.failed'], true)) {
                $result = $event;
                $candidate = $event['model'] ?? null;
                $model ??= is_string($candidate) && $candidate !== '' ? $candidate : null;
            }
        }

        if ($result === null) {
            $payload = ['type' => 'result', 'status' => $events === [] ? 'empty' : 'incomplete'];

            return new self($payload, false, 'La corrida terminó sin mensaje result.', $events, model: $model);
        }

        $subtype = isset($result['subtype'])
            ? (string) $result['subtype']
            : (($result['type'] ?? null) === 'turn.failed' ? 'error_during_execution' : null);
        $isError = ($result['is_error'] ?? false) === true || ($subtype !== null && str_starts_with($subtype, 'error'));
        $error = $isError ? self::errorFrom($result, $subtype) : null;

        return new self(
            payload: $result,
            ok: ! $isError,
            error: $error,
            transcript: $events,
            sessionId: self::stringValue($result['session_id'] ?? self::threadId($events)),
            costUsd: self::stringValue($result['total_cost_usd'] ?? null),
            subtype: $isError ? $subtype : null,
            model: $model,
        );
    }

    /** @param array<string, mixed> $result */
    private static function errorFrom(array $result, ?string $subtype): string
    {
        $message = $result['result'] ?? $result['error'] ?? null;

        if (is_string($message) && trim($message) !== '') {
            return $message;
        }

        return $subtype === null || $subtype === '' ? 'La corrida falló.' : 'La corrida falló ('.$subtype.').';
    }

    private static function stringValue(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? $value : null;
    }

    /** @param list<array<string, mixed>> $events */
    private static function threadId(array $events): ?string
    {
        foreach ($events as $event) {
            if (($event['type'] ?? null) === 'thread.started') {
                return self::stringValue($event['thread_id'] ?? null);
            }
        }

        return null;
    }
}
