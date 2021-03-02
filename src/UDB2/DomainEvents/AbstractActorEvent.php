<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\UDB2\DomainEvents;

use Broadway\Serializer\Serializable;
use ValueObjects\StringLiteral\StringLiteral;
use ValueObjects\Web\Url;

abstract class AbstractActorEvent implements Serializable
{
    use HasActorIdTrait, HasAuthoringMetadataTrait, HasUrlTrait {
        HasActorIdTrait::serialize as serializeActorId;
        HasAuthoringMetadataTrait::serialize as serializeAuthoringMetadata;
        HasUrlTrait::serialize as serializeUrl;
    }


    public function __construct(
        StringLiteral $actorId,
        \DateTimeImmutable $time,
        StringLiteral $author,
        Url $url
    ) {
        $this->setActorId($actorId);
        $this->setTime($time);
        $this->setAuthor($author);
        $this->setUrl($url);
    }

    /**
     * @return array
     */
    public function serialize()
    {
        return $this->serializeActorId() +
            $this->serializeAuthoringMetadata() +
            $this->serializeUrl();
    }

    /**
     * @return static
     */
    public static function deserialize(array $data)
    {
        /** @phpstan-ignore-next-line */
        return new static(
            new StringLiteral($data['actorId']),
            ISO8601DateTimeDeserializer::deserialize(
                new StringLiteral($data['time'])
            ),
            new StringLiteral($data['author']),
            Url::fromNative($data['url'])
        );
    }
}
