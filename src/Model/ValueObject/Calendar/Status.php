<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Calendar;

final class Status
{
    /**
     * @var StatusType
     */
    private $type;

    /**
     * @var TranslatedStatusReason|null
     */
    private $reason;

    public function __construct(StatusType $type, ?TranslatedStatusReason $reason = null)
    {
        $this->type = $type;
        $this->reason = $reason;
    }

    public function getType(): StatusType
    {
        return $this->type;
    }

    public function getReason(): ?TranslatedStatusReason
    {
        return $this->reason;
    }
}
