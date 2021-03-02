<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Commands\Moderation;

use Cake\Chronos\Chronos;

abstract class AbstractPublish extends AbstractModerationCommand
{
    /** @var  \DateTimeInterface */
    private $publicationDate;

    /**
     * AbstractPublish constructor.
     * @param string $itemId
     */
    public function __construct($itemId, \DateTimeInterface $publicationDate = null)
    {
        parent::__construct($itemId);

        $now = Chronos::now();

        if (is_null($publicationDate) || $publicationDate < $now) {
            $publicationDate = $now;
        }
        $this->publicationDate = $publicationDate;
    }

    /**
     * @return \DateTimeInterface
     */
    public function getPublicationDate()
    {
        return $this->publicationDate;
    }
}
