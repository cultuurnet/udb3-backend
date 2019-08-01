<?php
namespace CultuurNet\UDB3\UDB2\Actor\Events;

use CultuurNet\UDB2DomainEvents\ActorCreated;
use CultuurNet\UDB3\Cdb\CdbXmlContainerInterface;
use CultuurNet\UDB3\HasCdbXmlTrait;
use ValueObjects\StringLiteral\StringLiteral;
use ValueObjects\Web\Url;

class ActorCreatedEnrichedWithCdbXml extends ActorCreated implements CdbXmlContainerInterface
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

        $this->setCdbXml((string)$cdbXml);
        $this->setCdbXmlNamespaceUri((string)$cdbXmlNamespaceUri);
    }

    public static function fromActorCreated(
        ActorCreated $actorCreated,
        StringLiteral $cdbXml,
        StringLiteral $cdbXmlNamespaceUri
    ) {
        return new self(
            $actorCreated->getActorId(),
            $actorCreated->getTime(),
            $actorCreated->getAuthor(),
            $actorCreated->getUrl(),
            $cdbXml,
            $cdbXmlNamespaceUri
        );
    }
}
