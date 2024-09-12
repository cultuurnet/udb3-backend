<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\ReadModel\Metadata;

final class OfferMetadata
{
    private string $offerId;

    private string $createdByApiConsumer;

    public function __construct(
        string $offerId,
        string $createdByApiConsumer
    ) {
        $this->offerId = $offerId;
        $this->createdByApiConsumer = $createdByApiConsumer;
    }

    public static function default(string $offerId): self
    {
        return new self($offerId, 'unknown');
    }

    public function getOfferId(): string
    {
        return $this->offerId;
    }

    public function getCreatedByApiConsumer(): string
    {
        return $this->createdByApiConsumer;
    }

    public function withCreatedByApiConsumer(string $createdByApiConsumer): self
    {
        $clone = clone $this;
        $clone->createdByApiConsumer = $createdByApiConsumer;

        return $clone;
    }
}
