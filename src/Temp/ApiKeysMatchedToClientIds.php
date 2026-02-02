<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Temp;

interface ApiKeysMatchedToClientIds
{
    public function getClientId(string $apiKey): string;
}
