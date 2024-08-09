<?php

declare(strict_types=1);

namespace CultuurNet\UDB3;

final class SampleFiles
{
    public static function read(string $filepath): string
    {
        $content = file_get_contents($filepath);

        if ($content === false) {
            throw new \RuntimeException('Failed to read file ' . $filepath);
        }

        return $content;
    }
}
