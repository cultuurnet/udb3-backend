<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\CommandHandling;

interface AsyncCommand
{
    public function setAsyncCommandId(string $commandId): void;
    public function getAsyncCommandId(): ?string;
}
