<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\User\Exceptions;

use Exception;

final class UnmatchedApiKey extends Exception
{
    public function __construct(readonly string $apiKey)
    {
        parent::__construct($this->apiKey . ' could not be matched to a clientId.');
    }
}
