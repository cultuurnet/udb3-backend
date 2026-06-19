<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Events;

use CultuurNet\UDB3\Model\Serializer\ValueObject\Audience\AudienceTypeDenormalizer;
use CultuurNet\UDB3\Model\Serializer\ValueObject\Audience\AudienceTypeNormalizer;
use CultuurNet\UDB3\Model\ValueObject\Audience\AudienceType;
use CultuurNet\UDB3\Offer\Events\AbstractEvent;
use InvalidArgumentException;

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
        try {
            $audienceType = (new AudienceTypeDenormalizer())->denormalize($data['audience'], AudienceType::class);
        } catch (InvalidArgumentException $e) {
            // Legacy events may contain audience types that are no longer supported
            // (e.g. "childrenOnly", now expressed as a separate boolean). Fall back to
            // everyone so replay does not crash on historical data.
            $audienceType = AudienceType::everyone();
        }

        return new self($data['item_id'], $audienceType);
    }
}
