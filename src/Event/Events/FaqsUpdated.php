<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Events;

use CultuurNet\UDB3\Event\Serializer\FaqsDenormalizer;
use CultuurNet\UDB3\Model\Serializer\ValueObject\Faq\FaqsNormalizer;
use CultuurNet\UDB3\Model\ValueObject\Faq\Faqs;
use CultuurNet\UDB3\Offer\Events\AbstractEvent;

final class FaqsUpdated extends AbstractEvent
{
    public function __construct(string $itemId, public readonly Faqs $faqs)
    {
        parent::__construct($itemId);
    }

    public function serialize(): array
    {
        return parent::serialize() + [
            'faqs' => (new FaqsNormalizer())->normalize($this->faqs),
        ];
    }

    public static function deserialize(array $data): self
    {
        $faqs = (new FaqsDenormalizer())->denormalize($data['faqs'], Faqs::class);
        return new self($data['item_id'], $faqs);
    }
}
