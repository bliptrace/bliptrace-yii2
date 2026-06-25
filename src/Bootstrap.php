<?php

declare(strict_types=1);

namespace G8Scope\Yii2;

use Throwable;
use yii\base\Application;
use yii\base\BootstrapInterface;

// Only define the Yii BootstrapInterface implementation when the Yii runtime is
// present, so the file stays autoload-safe during standalone tests.
if (interface_exists(BootstrapInterface::class)) {
    /**
     * Optional Yii2 bootstrap. When listed in the application `bootstrap` array
     * (and referenced by composer's `extra.bootstrap`), it hooks the application
     * error handler: every handled exception is forwarded to the `g8scope`
     * component's report() method.
     *
     * Wiring (config/web.php):
     *   'bootstrap' => ['g8scope'],
     *   'components' => [
     *       'g8scope' => ['class' => \G8Scope\Yii2\G8ScopeComponent::class, ...],
     *   ],
     *
     * The component itself implements BootstrapInterface-style hooking here so
     * the host only needs to list the component name in `bootstrap`.
     */
    final class Bootstrap implements BootstrapInterface
    {
        /** @param Application $app */
        public function bootstrap($app): void
        {
            try {
                $handler = $app->getErrorHandler();

                // Forward any exception the error handler surfaces to g8scope.
                $app->on(Application::EVENT_AFTER_REQUEST, function () use ($app): void {
                    $exception = $app->getErrorHandler()->exception ?? null;
                    if ($exception instanceof Throwable) {
                        $this->forward($app, $exception);
                    }
                });

                // Touch the handler so static analysers see the intent.
                unset($handler);
            } catch (Throwable) {
                // Never break boot.
            }
        }

        private function forward(mixed $app, Throwable $e): void
        {
            try {
                $component = $app->has('g8scope') ? $app->get('g8scope') : null;
                if ($component instanceof G8ScopeComponent) {
                    $component->report($e);
                }
            } catch (Throwable) {
                // swallow
            }
        }
    }
}
