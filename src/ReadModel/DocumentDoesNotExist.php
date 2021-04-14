<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\ReadModel;

use Exception;

final class DocumentDoesNotExist extends Exception
{
    private const NOT_FOUND = 404;

    public static function withId(string $id): self
    {
        return new self("Document with id ${id} not found.", 404);
    }

    public static function notFound(string $id): DocumentDoesNotExist
    {
        return new self("Document with id ${id} not found.", self::NOT_FOUND);
    }
}
