<?php

declare(strict_types=1);

namespace CultuurNet\UDB3;

interface JsonLdSerializableInterface
{
    public function toJsonLd(): array;
}
