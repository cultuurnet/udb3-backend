<?php

declare(strict_types=1);

namespace CultuurNet\UDB3;

interface SameAsInterface
{
    public function generateSameAs(string $eventId, string $name): array;
}
