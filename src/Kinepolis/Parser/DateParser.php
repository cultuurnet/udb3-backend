<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Kinepolis\Parser;

interface DateParser
{
    public function processDates(array $dates, int $length): array;
}
