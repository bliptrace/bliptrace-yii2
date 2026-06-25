<?php

declare(strict_types=1);

namespace G8Scope\Yii2\Context;

/**
 * Builds the envelope context from the current Yii2 request (if any).
 *
 * Accepts a plain request descriptor so it is fully testable without the Yii
 * runtime. When called from the component, the descriptor is derived from
 * \yii\web\Request. Sensitive headers (authorization, cookie) are stripped.
 */
final class RequestContext
{
    /**
     * @param  array<string,mixed>|null  $request  ['method'=>..,'url'=>..,'headers'=>[..]]
     * @param  array<string,mixed>|null  $user  ['id'=>..]
     * @return array<string,mixed>
     */
    public static function capture(?array $request = null, ?array $user = null): array
    {
        $context = ['custom' => []];

        if ($request !== null && ($request['method'] ?? null) !== null) {
            $context['request'] = [
                'method' => (string) $request['method'],
                'url' => (string) ($request['url'] ?? ''),
                'headers' => self::scrubHeaders($request['headers'] ?? []),
            ];
        }

        if ($user !== null && ($user['id'] ?? null) !== null) {
            $context['user'] = ['id' => (string) $user['id']];
        }

        return $context;
    }

    /**
     * Build a request descriptor from a Yii2 web request object. Guarded so this
     * class never hard-depends on Yii at autoload time.
     *
     * @return array<string,mixed>|null
     */
    public static function fromYiiRequest(mixed $request): ?array
    {
        if (! is_object($request) || ! method_exists($request, 'getMethod')) {
            return null;
        }

        $headers = [];
        if (method_exists($request, 'getHeaders')) {
            foreach ($request->getHeaders() as $name => $value) {
                $headers[$name] = is_array($value) ? implode(',', $value) : $value;
            }
        }

        $url = '';
        if (method_exists($request, 'getAbsoluteUrl')) {
            $url = (string) $request->getAbsoluteUrl();
        }

        return [
            'method' => (string) $request->getMethod(),
            'url' => $url,
            'headers' => $headers,
        ];
    }

    /**
     * @param  array<string,mixed>  $headers
     * @return array<string,string>
     */
    private static function scrubHeaders(array $headers): array
    {
        $out = [];
        foreach ($headers as $name => $value) {
            if (in_array(strtolower((string) $name), ['authorization', 'cookie'], true)) {
                continue;
            }
            $out[(string) $name] = is_array($value) ? implode(',', $value) : (string) $value;
        }

        return $out;
    }
}
