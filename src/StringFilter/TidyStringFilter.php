<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\StringFilter;

class TidyStringFilter implements StringFilterInterface
{
    public function filter(string $string): string
    {
        $config = ['show-body-only' => true];

        /** @var \tidy $tidy */
        $tidy = tidy_parse_string($string, $config, 'UTF8');
        $tidy->cleanRepair();

        return tidy_get_output($tidy);
    }
}
