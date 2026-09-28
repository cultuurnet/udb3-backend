<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Support;

final class Poll
{
    private const INTERVAL_MICROSECONDS = 200_000;

    /**
     * Calls $isDone until it returns true or $timeoutSeconds have passed, checking once more at the end.
     */
    public static function until(callable $isDone, int $timeoutSeconds): void
    {
        $deadline = microtime(true) + $timeoutSeconds;

        while (!$isDone() && microtime(true) < $deadline) {
            usleep(self::INTERVAL_MICROSECONDS);
        }
    }
}
