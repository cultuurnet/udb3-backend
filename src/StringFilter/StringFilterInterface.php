<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\StringFilter;

interface StringFilterInterface
{
    public function filter(string $string): string;
}
