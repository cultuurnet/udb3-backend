<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\ReadModel;

use Exception;

final class DocumentDoesNotExist extends Exception
{
    public static function withId(string $id): self
    {
        return new self("Document with id ${id} not found.", 404);
    }
}
