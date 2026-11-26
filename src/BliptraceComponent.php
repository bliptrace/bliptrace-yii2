<?php

declare(strict_types=1);

namespace Bliptrace\Yii2;

use Bliptrace\Php\Client;
use Bliptrace\Php\Config;
use Bliptrace\Yii2\Context\RequestContext;
use Throwable;
use yii\base\Component;

// Only define a Yii-coupled component when the Yii runtime is present. This keeps
// the file autoload-safe at test time (no yiisoft/yii2 required to run tests).
if (class_exists(Component::class)) {
    /**
     * Yii2 application component. Register it in `components` (and `bootstrap`)
     * and configure its public props; on init it builds a Bliptrace Client from a
     * {@see Config} and exposes {@see report()} which the error handler forwards to.
     *
     * The `project` is NEVER sent in the payload — it is resolved server-side
     * from the ingest key.
     */
    final class BliptraceComponent extends Component
    {
        public bool $enabled = true;

        public ?string $key = null;

        public string $endpoint = 'https://ingest.bliptrace.com/v1/ingest';

        public string $environment = 'production';

        public ?string $release = null;

        public int $timeout = 3;

        public int $maxFrames = 30;

        public int $maxMessage = 2000;

        /** @var array<int,string> */
        public array $ignoreExceptions = [];

        /** @var array<int,string> */
        public array $scrubKeys = [];

        private ?Reporter $reporter = null;

        public function init(): void
        {
            parent::init();

            try {
                $config = new Config(
                    key: $this->key,
                    endpoint: $this->endpoint,
                    environment: $this->environment,
                    release: $this->release,
                    timeout: $this->timeout,
                    maxFrames: $this->maxFrames,
                    maxMessage: $this->maxMessage,
                    ignoreExceptions: $this->ignoreExceptions,
                    scrubKeys: $this->scrubKeys,
                    basePath: defined('YII2_PATH') ? '' : '',
                    enabled: $this->enabled,
                    sdkName: 'bliptrace-yii2',
                    sdkVersion: '1.0.0',
                );

                $this->reporter = new Reporter(
                    new Client($config),
                    RequestContext::fromYiiRequest($this->resolveRequest()),
                    $this->resolveUser(),
                );
            } catch (Throwable) {
                // Never break application boot.
                $this->reporter = null;
            }
        }

        /** Forward an exception to the reporter. Never throws. */
        public function report(Throwable $e): void
        {
            $this->reporter?->capture($e);
        }

        private function resolveRequest(): mixed
        {
            try {
                return \Yii::$app?->getRequest();
            } catch (Throwable) {
                return null;
            }
        }

        /** @return array<string,mixed>|null */
        private function resolveUser(): ?array
        {
            try {
                $user = \Yii::$app?->getUser();
                $id = $user?->getId();

                return $id !== null ? ['id' => $id] : null;
            } catch (Throwable) {
                return null;
            }
        }
    }
}
