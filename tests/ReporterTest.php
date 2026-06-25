<?php

declare(strict_types=1);

use G8Scope\Php\Client;
use G8Scope\Php\Config;
use G8Scope\Php\Transport\Transport;
use G8Scope\Yii2\Reporter;

function reporterWith(FakeTransport $t, array $config = [], ?array $request = null, ?array $user = null): Reporter
{
    $client = new Client(new Config(...array_merge(['key' => 'k', 'endpoint' => 'http://api'], $config)), $t);

    return new Reporter($client, $request, $user);
}

it('builds and ships a valid envelope', function (): void {
    $t = new FakeTransport;
    reporterWith($t)->capture(new RuntimeException('boom'));

    expect($t->sent)->toHaveCount(1);

    $env = $t->sent[0]['envelope'];
    expect($env['exception']['type'])->toBe('RuntimeException')
        ->and($env['exception']['message'])->toBe('boom')
        ->and($env['runtime']['name'])->toBe('php')
        ->and($env['sdk']['name'])->toBe('g8scope-php')
        ->and($t->sent[0]['endpoint'])->toBe('http://api')
        ->and($t->sent[0]['key'])->toBe('k');
});

it('never sends the project in the payload', function (): void {
    $t = new FakeTransport;
    reporterWith($t)->capture(new RuntimeException('boom'));

    expect($t->sent[0]['envelope'])->not->toHaveKey('project');
});

it('skips ignored exception types', function (): void {
    $t = new FakeTransport;
    reporterWith($t, ['ignoreExceptions' => [InvalidArgumentException::class]])
        ->capture(new InvalidArgumentException('nope'));

    expect($t->sent)->toBeEmpty();
});

it('respects the kill switch', function (): void {
    $t = new FakeTransport;
    reporterWith($t, ['enabled' => false])->capture(new Exception('x'));

    expect($t->sent)->toBeEmpty();
});

it('does nothing when no key is configured', function (): void {
    $t = new FakeTransport;
    reporterWith($t, ['key' => null])->capture(new Exception('x'));

    expect($t->sent)->toBeEmpty();
});

it('never throws into the host app', function (): void {
    // A client with a transport that explodes must not surface the error.
    $boom = new class implements Transport
    {
        public function send(string $endpoint, string $key, array $envelope, int $timeout): void
        {
            throw new RuntimeException('transport down');
        }
    };

    $reporter = new Reporter(new Client(new Config(key: 'k'), $boom));

    expect(fn () => $reporter->capture(new Exception('x')))->not->toThrow(Throwable::class);
});

it('enriches with request context when provided', function (): void {
    $t = new FakeTransport;
    $request = [
        'method' => 'GET',
        'url' => 'https://app.test/orders',
        'headers' => ['X-Custom' => 'v'],
    ];
    reporterWith($t, [], $request, ['id' => 42])->capture(new RuntimeException('boom'));

    $ctx = $t->sent[0]['envelope']['context'];
    expect($ctx['request']['method'])->toBe('GET')
        ->and($ctx['request']['url'])->toBe('https://app.test/orders')
        ->and($ctx['user']['id'])->toBe('42');
});
