<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventBus\Middleware;

use Broadway\Domain\DomainEventStream;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use CultuurNet\UDB3\EventBus\EventBusMiddleware;

final class DisableMailsMiddleware implements EventBusMiddleware
{
    private static bool $disableMails = false;

    public static function disableMails(): void
    {
        self::$disableMails = true;
    }

    public static function enableMails(): void
    {
        self::$disableMails = false;
    }

    public function beforePublish(DomainEventStream $domainEventStream): DomainEventStream
    {
        if (!self::$disableMails) {
            return $domainEventStream;
        }

        $messages = [];
        foreach ($domainEventStream as $domainMessage) {
            if ($domainMessage instanceof DomainMessage) {
                $domainMessage = $domainMessage->andMetadata(
                    new Metadata(['disable_mails' => true])
                );
            }
            $messages[] = $domainMessage;
        }
        return new DomainEventStream($messages);
    }
}
