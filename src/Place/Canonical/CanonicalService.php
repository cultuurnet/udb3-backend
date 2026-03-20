<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place\Canonical;

interface CanonicalService
{
    public function getCanonical(string $clusterId): string;
    public function getCanonicalFromArrayWithoutThrowing(array $placeIds): string;
}
