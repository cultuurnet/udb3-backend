<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Verenigingsloket\Exception;

class VerenigingsloketApiFailure extends \DomainException
{
    public static function apiUnavailable(string $errorMessage): self
    {
        return new self('Verenigingsloket API is unavailable: ' . $errorMessage);
    }

    public static function requestFailed(int $statusCode): self
    {
        return new self('Verenigingsloket API request failed: HTTP ' . $statusCode);
    }
}
