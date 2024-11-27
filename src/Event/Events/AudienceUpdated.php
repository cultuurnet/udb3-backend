<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Events;

use CultuurNet\UDB3\Model\Serializer\ValueObject\Audience\AudienceTypeDenormalizer;
use CultuurNet\UDB3\Model\Serializer\ValueObject\Audience\AudienceTypeNormalizer;
use CultuurNet\UDB3\Model\ValueObject\Audience\AudienceType;
use CultuurNet\UDB3\Offer\Events\AbstractEvent;

final class AudienceUpdated extends AbstractEvent
{
    private AudienceType $audienceType;

    public function __construct(
        string $itemId,
        AudienceType $audienceType
    ) {
        parent::__construct($itemId);
        $this->audienceType = $audienceType;
    }

    public function getAudienceType(): AudienceType
    {
        return $this->audienceType;
    }

    public function serialize(): array
    {
        return parent::serialize() + [
            'audience' => (new AudienceTypeNormalizer())->normalize($this->audienceType),
        ];
    }

    public static function deserialize(array $data): AudienceUpdated
    {
        return new self(
            $data['item_id'],
            (new AudienceTypeDenormalizer())->denormalize($data['audience'], AudienceType::class)
        );
    }
}
