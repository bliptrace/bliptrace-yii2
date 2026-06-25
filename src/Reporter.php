<?php

declare(strict_types=1);

namespace G8Scope\Yii2;

use G8Scope\Php\Client;
use G8Scope\Yii2\Context\RequestContext;
use Throwable;

/**
 * Yii2 reporter: enrich an exception with request context and ship it via the
 * runtime PHP client. Never throws into the host app.
 */
final class Reporter
{
    /** @param array<string,mixed>|null $request Plain request descriptor (testable without Yii). */
    public function __construct(
        private readonly Client $client,
        private readonly ?array $request = null,
        private readonly ?array $user = null,
    ) {}

    public function capture(Throwable $e): void
    {
        try {
            $context = RequestContext::capture($this->request, $this->user);
            $this->client->capture($e, $context);
        } catch (Throwable) {
            // Never break the app.
        }
    }
}
