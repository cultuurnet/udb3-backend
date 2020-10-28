<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\ReadModel;

use Exception;

final class DocumentDoesNotExist extends Exception
{
    private const NOT_FOUND = 404;
    private const GONE = 410;

    public static function notFound(string $id): DocumentDoesNotExist
    {
        return new self("Document with id ${id} not found.", self::NOT_FOUND);
    }

    public static function gone(string $id): DocumentDoesNotExist
    {
        return new self("Document with id ${id} was removed.", self::GONE);
    }

    public function isNotFound(): bool
    {
        return $this->code === self::NOT_FOUND;
    }

    public function isGone(): bool
    {
        return $this->code === self::GONE;
    }
}
