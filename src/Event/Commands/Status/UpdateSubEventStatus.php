<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Commands\Status;

use CultuurNet\UDB3\Event\ValueObjects\Status;
use CultuurNet\UDB3\Offer\Commands\AbstractCommand;
use CultuurNet\UDB3\Timestamp;

final class UpdateSubEventStatus extends AbstractCommand
{
    /**
     * @var Status
     */
    private $status;

    /**
     * @var Timestamp
     */
    private $timestamp;

    /**
     * @var string
     */
    private $reason;

    public function __construct(string $id, Status $status, Timestamp $timestamp, string $reason)
    {
        parent::__construct($id);
        $this->status = $status;
        $this->timestamp = $timestamp;
        $this->reason = $reason;
    }

    public function getStatus(): Status
    {
        return $this->status;
    }

    public function getTimestamp(): Timestamp
    {
        return $this->timestamp;
    }

    public function getReason(): string
    {
        return $this->reason;
    }
}
