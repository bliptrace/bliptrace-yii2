# bliptrace/yii2

Yii2 SDK for **Bliptrace** — auto-captures exceptions and ships them to the Bliptrace ingest API. This is a **thin adapter** over the runtime PHP SDK [`bliptrace/php`](../bliptrace-php): it adds a Yii2 application component, an error-handler hook, request-context enrichment, and a Reporter. It never throws into the host application.

## Requirements

- PHP `^8.2`
- `bliptrace/php` (runtime SDK, pulled automatically)
- `yiisoft/yii2:^2.0` (your application — listed as a `suggest`)

## Installation

```bash
composer require bliptrace/yii2
```

## Wiring

Register the component under `components` and list it in `bootstrap` so it is
initialised on every request. The optional `Bootstrap` class hooks the Yii error
handler and forwards handled exceptions to the component.

```php
// config/web.php
return [
    'bootstrap' => ['bliptrace'],

    'components' => [
        'bliptrace' => [
            'class'       => \Bliptrace\Yii2\BliptraceComponent::class,
            'enabled'     => filter_var(\Bliptrace\Php\Env::get('ENABLED', 'true'), FILTER_VALIDATE_BOOL),
            'key'         => \Bliptrace\Php\Env::get('KEY'),
            'endpoint'    => \Bliptrace\Php\Env::get('ENDPOINT', 'https://ingest.bliptrace.com/v1/ingest'),
            'environment' => YII_ENV,
            'release'     => \Bliptrace\Php\Env::get('RELEASE'),
            'timeout'     => 3,
            'maxFrames'   => 30,
            'maxMessage'  => 2000,
            'ignoreExceptions' => [
                \yii\web\NotFoundHttpException::class,
            ],
            'scrubKeys'   => ['password', 'token', 'secret'],
        ],
    ],
];
```

> **Env names:** `Bliptrace\Php\Env::get('KEY')` (from `bliptrace/php` ^2.0) reads `BLIPTRACE_KEY`.

### Configuration

| Property | Maps to `Bliptrace\Php\Config` | Notes |
|---|---|---|
| `enabled` | `enabled` | Master kill switch. |
| `key` | `key` | Ingest key — resolves the **project server-side** (never sent in the payload). |
| `endpoint` | `endpoint` | Ingest URL (a bare base URL also works: `/v1/ingest` is appended when missing); sent with `X-Ingest-Key`. |
| `environment` | `environment` | e.g. `production`, `staging`. |
| `release` | `release` | App release / version. |
| `timeout` | `timeout` | HTTP timeout (seconds). |
| `maxFrames` | `maxFrames` | Max stack frames captured. |
| `maxMessage` | `maxMessage` | Max message length. |
| `ignoreExceptions` | `ignoreExceptions` | Exception classes to skip. |
| `scrubKeys` | `scrubKeys` | Context keys to scrub before send (PII protection). |

## How the error handler hook works

When `Bootstrap` is active (via `extra.bootstrap` in `composer.json` or by being
listed in the app `bootstrap` array), it subscribes to the application
`EVENT_AFTER_REQUEST` event. If the Yii error handler surfaced an exception
during the request, that exception is forwarded to the `bliptrace` component's
`report()` method, which enriches it with request context and ships it.

You can also report manually anywhere in your app:

```php
try {
    // ...
} catch (\Throwable $e) {
    Yii::$app->bliptrace->report($e);
}
```

## Request context

Each captured envelope is enriched with:

- `request`: `method`, absolute `url`, and headers **minus** `authorization` and `cookie`.
- `user`: the authenticated user `id` when available.
- `custom`: an empty map by default.

The project is **never** trusted from the payload — it is always resolved
server-side from the ingest key.

## Testing

The test suite runs standalone against only `bliptrace/php` — the Yii framework is
not required:

```bash
composer install
vendor/bin/pest
```

## Licence

MIT. British spelling used throughout the documentation.
