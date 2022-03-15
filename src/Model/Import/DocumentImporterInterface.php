<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Import;

use CultuurNet\UDB3\ApiGuard\Consumer\Consumer;

/**
 * @deprecated Should be removed when all implementations have been removed (see deprecated tags on implementations)
 */
interface DocumentImporterInterface
{
    public function import(DecodedDocument $decodedDocument, Consumer $consumer = null): ?string;
}
