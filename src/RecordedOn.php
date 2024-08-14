<?php

declare(strict_types=1);

namespace CultuurNet\UDB3;

use Broadway\Domain\DomainMessage;
use Broadway\Domain\DateTime;

class RecordedOn
{
    /**
     * @var DateTime
     */
    private $recorded;

    /**
     * ModifiedDateTime constructor.
     */
    private function __construct(DateTime $recorded)
    {
        $this->recorded = $recorded;
    }

    /**
     * @return RecordedOn
     */
    public static function fromDomainMessage(DomainMessage $domainMessage)
    {
        return new self($domainMessage->getRecordedOn());
    }

    /**
     * @return RecordedOn
     */
    public static function fromBroadwayDateTime(DateTime $dateTime)
    {
        return new self($dateTime);
    }

    /**
     * @return DateTime
     */
    public function toBroadwayDateTime()
    {
        return $this->recorded;
    }

    /**
     * @return string
     */
    public function toString()
    {
        return DateTimeFactory::fromFormat(
            DateTime::FORMAT_STRING,
            $this->recorded->toString()
        )->format('c');
    }
}
