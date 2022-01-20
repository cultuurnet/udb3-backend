<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\ValueObjects;

use Broadway\Serializer\Serializable;
use CultuurNet\UDB3\Model\ValueObject\Audience\AudienceType;

/**
 * @deprecated
 *   Use CultuurNet\UDB3\Model\ValueObject\Audience\AudienceType instead where possible
 */
final class Audience implements Serializable
{
    private AudienceType $audienceType;

    public function __construct(AudienceType $audienceType)
    {
        $this->audienceType = $audienceType;
    }

    public function getAudienceType(): AudienceType
    {
        return $this->audienceType;
    }

    public static function deserialize(array $data): Audience
    {
        return new self(
            new AudienceType($data['audienceType'])
        );
    }

    public function serialize(): array
    {
        return [
            'audienceType' => $this->getAudienceType()->toString(),
        ];
    }

    public function equals(Audience $otherAudience): bool
    {
        return $this->getAudienceType()->sameAs($otherAudience->getAudienceType());
    }
}
