<?php

declare(strict_types=1);

use G8Scope\Php\Transport\Transport;

/** In-memory transport that records what would have been shipped. */
final class FakeTransport implements Transport
{
    /** @var array<int,array<string,mixed>> */
    public array $sent = [];

    public function send(string $endpoint, string $key, array $envelope, int $timeout): void
    {
        $this->sent[] = compact('endpoint', 'key', 'envelope');
    }
}
