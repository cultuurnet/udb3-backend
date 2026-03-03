<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Doctrine;

interface DatabaseConnectionChecker
{
    public function ensureConnection(): void;
}
