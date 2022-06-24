<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\CommandHandling;

trait AsyncCommandTrait
{
    private ?string $asyncCommandId = null;

    public function setAsyncCommandId(string $commandId): void
    {
        $this->asyncCommandId = $commandId;
    }

    public function getAsyncCommandId(): ?string
    {
        return $this->asyncCommandId;
    }
}
