<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Events\Moderation;

use CultuurNet\UDB3\DateTimeFactory;
use CultuurNet\UDB3\Offer\Events\AbstractEvent;
use DateTimeInterface;

abstract class AbstractPublished extends AbstractEvent
{
    private DateTimeInterface $publicationDate;

    final public function __construct(string $itemId, DateTimeInterface $publicationDate)
    {
        parent::__construct($itemId);

        $this->publicationDate = $publicationDate;
    }

    public function getPublicationDate(): DateTimeInterface
    {
        return $this->publicationDate;
    }

    public function serialize(): array
    {
        return parent::serialize() + [
            'publication_date' => $this->publicationDate->format(DateTimeInterface::ATOM),
        ];
    }

    public static function deserialize(array $data): AbstractPublished
    {
        return new static(
            $data['item_id'],
            DateTimeFactory::fromAtom($data['publication_date'])
        );
    }
}
