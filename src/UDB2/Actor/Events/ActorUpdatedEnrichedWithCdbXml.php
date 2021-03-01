<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\UDB2\Actor\Events;

use CultuurNet\UDB3\Cdb\CdbXmlContainerInterface;
use CultuurNet\UDB3\HasCdbXmlTrait;
use CultuurNet\UDB3\UDB2\DomainEvents\ActorUpdated;
use ValueObjects\StringLiteral\StringLiteral;
use ValueObjects\Web\Url;

class ActorUpdatedEnrichedWithCdbXml extends ActorUpdated implements CdbXmlContainerInterface
{
    use HasCdbXmlTrait;

    public function __construct(
        StringLiteral $actorId,
        \DateTimeImmutable $time,
        StringLiteral $author,
        Url $url,
        StringLiteral $cdbXml,
        StringLiteral $cdbXmlNamespaceUri
    ) {
        parent::__construct(
            $actorId,
            $time,
            $author,
            $url
        );

        $this->setCdbXml((string) $cdbXml);
        $this->setCdbXmlNamespaceUri((string) $cdbXmlNamespaceUri);
    }

    public static function fromActorUpdated(
        ActorUpdated $actorUpdated,
        StringLiteral $cdbXml,
        StringLiteral $cdbXmlNamespaceUri
    ) {
        return new self(
            $actorUpdated->getActorId(),
            $actorUpdated->getTime(),
            $actorUpdated->getAuthor(),
            $actorUpdated->getUrl(),
            $cdbXml,
            $cdbXmlNamespaceUri
        );
    }
}
