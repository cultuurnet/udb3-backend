<?php

declare(strict_types=1);

namespace CultuurNet\UDB3;

use Broadway\Domain\DomainMessage;
use Broadway\Domain\DateTime;

class RecordedOn
{
    private DateTime $recorded;

    /**
     * ModifiedDateTime constructor.
     */
    private function __construct(DateTime $recorded)
    {
        $this->recorded = $recorded;
    }

    public static function fromDomainMessage(DomainMessage $domainMessage): RecordedOn
    {
        return new self($domainMessage->getRecordedOn());
    }

    public static function fromBroadwayDateTime(DateTime $dateTime): RecordedOn
    {
        return new self($dateTime);
    }

    public function toBroadwayDateTime(): DateTime
    {
        return $this->recorded;
    }

    public function toString(): string
    {
        return DateTimeFactory::fromFormat(
            DateTime::FORMAT_STRING,
            $this->recorded->toString()
        )->format('c');
    }
}
