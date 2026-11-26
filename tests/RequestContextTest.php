<?php

declare(strict_types=1);

use Bliptrace\Yii2\Context\RequestContext;

it('yields an empty custom map and no request when nothing is provided', function (): void {
    $ctx = RequestContext::capture();

    expect($ctx)->toBe(['custom' => []])
        ->and($ctx)->not->toHaveKey('request')
        ->and($ctx)->not->toHaveKey('user');
});

it('captures method and url from a request descriptor', function (): void {
    $ctx = RequestContext::capture([
        'method' => 'POST',
        'url' => 'https://app.test/checkout',
        'headers' => [],
    ]);

    expect($ctx['request']['method'])->toBe('POST')
        ->and($ctx['request']['url'])->toBe('https://app.test/checkout');
});

it('strips authorization and cookie headers', function (): void {
    $ctx = RequestContext::capture([
        'method' => 'GET',
        'url' => 'https://app.test',
        'headers' => [
            'Authorization' => 'Bearer secret',
            'Cookie' => 'session=abc',
            'X-Request-Id' => 'r-1',
        ],
    ]);

    expect($ctx['request']['headers'])->toBe(['X-Request-Id' => 'r-1'])
        ->and($ctx['request']['headers'])->not->toHaveKey('Authorization')
        ->and($ctx['request']['headers'])->not->toHaveKey('Cookie');
});

it('includes the user id when given', function (): void {
    $ctx = RequestContext::capture(null, ['id' => 7]);

    expect($ctx['user']['id'])->toBe('7');
});

it('builds a descriptor from a Yii-like request object', function (): void {
    $request = new class
    {
        public function getMethod(): string
        {
            return 'PUT';
        }

        public function getAbsoluteUrl(): string
        {
            return 'https://app.test/profile';
        }

        public function getHeaders(): array
        {
            return ['Authorization' => 'Bearer x', 'Accept' => 'application/json'];
        }
    };

    $descriptor = RequestContext::fromYiiRequest($request);
    $ctx = RequestContext::capture($descriptor);

    expect($ctx['request']['method'])->toBe('PUT')
        ->and($ctx['request']['url'])->toBe('https://app.test/profile')
        ->and($ctx['request']['headers'])->toBe(['Accept' => 'application/json']);
});

it('returns null for a non-request object', function (): void {
    expect(RequestContext::fromYiiRequest(new stdClass))->toBeNull();
});
