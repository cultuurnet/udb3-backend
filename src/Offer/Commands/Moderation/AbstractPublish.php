<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Commands\Moderation;

use Cake\Chronos\Chronos;

abstract class AbstractPublish extends AbstractModerationCommand
{
    private \DateTimeInterface $publicationDate;

    public function __construct(string $itemId, \DateTimeInterface $publicationDate = null)
    {
        parent::__construct($itemId);

        $now = Chronos::now();

        if (is_null($publicationDate) || $publicationDate < $now) {
            $publicationDate = $now;
        }
        $this->publicationDate = $publicationDate;
    }

    public function getPublicationDate(): ?\DateTimeInterface
    {
        return $this->publicationDate;
    }
}
