<?php

namespace CultuurNet\UDB3\Event\ValueObjects;

use Broadway\Serializer\SerializableInterface;

final class Audience implements SerializableInterface
{
    /**
     * Store the Audience enum internally as a string to make sure that PHP encode works.
     * @var string
     */
    private $audienceType;

    /**
     * @param AudienceType $audienceType
     */
    public function __construct(AudienceType $audienceType)
    {
        $this->audienceType = $audienceType->toNative();
    }

    public function getAudienceType(): AudienceType
    {
        return AudienceType::fromNative($this->audienceType);
    }

    public static function deserialize(array $data): Audience
    {
        return new self(
            AudienceType::fromNative($data['audienceType'])
        );
    }

    public function serialize(): array
    {
        return [
            'audienceType' => $this->getAudienceType()->toNative(),
        ];
    }

    public function equals(Audience $otherAudience): bool
    {
        return $this->getAudienceType() === $otherAudience->getAudienceType();
    }
}
