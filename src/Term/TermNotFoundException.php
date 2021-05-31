<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Term;

use RuntimeException;

final class TermNotFoundException extends RuntimeException
{
    public static function forId(string $id): TermNotFoundException
    {
        return new self('No term found with id ' . $id);
    }
}
