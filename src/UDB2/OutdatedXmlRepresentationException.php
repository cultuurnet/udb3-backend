<?php
/**
 * @file
 */

namespace CultuurNet\UDB3\UDB2;

class OutdatedXmlRepresentationException extends \RuntimeException
{
    /**
     * @var \DateTimeInterface
     */
    private $actualDate;

    /**
     * @var \DateTimeInterface
     */
    private $sinceDate;

    /**
     * @var string
     */
    private $actorId;

    /**
     * @param string $message
     * @param string $actorId
     * @param \DateTimeInterface $sinceDate
     * @param \DateTimeInterface $actualDate
     */
    public function __construct($message, $actorId, \DateTimeInterface $sinceDate, \DateTimeInterface $actualDate)
    {
        parent::__construct(
            $message
        );

        $this->actorId = $actorId;
        $this->sinceDate = $sinceDate;
        $this->actualDate = $actualDate;
    }
}
