<?php

namespace CultuurNet\UDB3\Offer\Commands\Moderation;

abstract class AbstractPublish extends AbstractModerationCommand
{
    /** @var  \DateTimeInterface */
    private $publicationDate;

    /**
     * AbstractPublish constructor.
     * @param string $itemId
     * @param \DateTimeInterface|null $publicationDate
     */
    public function __construct($itemId, \DateTimeInterface $publicationDate = null)
    {
        parent::__construct($itemId);

        if (is_null($publicationDate)) {
            $publicationDate = new \DateTime();
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
