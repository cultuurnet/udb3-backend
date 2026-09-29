<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Support;

use RuntimeException;

final class Poll
{
    private const INTERVAL_MICROSECONDS = 200_000;

    /**
     * Calls $isDone until it returns true, and fails once $timeoutSeconds have passed without it.
     *
     * @throws RuntimeException
     */
    public static function until(callable $isDone, int $timeoutSeconds, string $waitingFor): void
    {
        $deadline = microtime(true) + $timeoutSeconds;

        while (!$isDone()) {
            if (microtime(true) >= $deadline) {
                throw new RuntimeException(
                    sprintf('Gave up after %d seconds waiting for %s', $timeoutSeconds, $waitingFor)
                );
            }

            usleep(self::INTERVAL_MICROSECONDS);
        }
    }
}
