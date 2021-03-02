<?php

declare(strict_types=1);

/**
 * @file
 * Contains CultuurNet\UDB3\JsonLdObjectInterface.
 */

namespace CultuurNet\UDB3;

/**
 * Interface for immutable objects that can be converted to json ld.
 */
interface JsonLdSerializableInterface
{
    /**
     * Convert the object to json ld.
     */
    public function toJsonLd();
}
