<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventSourcing;

use Broadway\Domain\Metadata;
use Broadway\EventSourcing\MetadataEnrichment\MetadataEnricher;

final class LazyCallbackMetadataEnricher implements MetadataEnricher
{
    /**
     * @var callable
     */
    private $metadataCallback;

    public function __construct(callable $metadataCallback)
    {
        $this->metadataCallback = $metadataCallback;
    }

    public function enrich(Metadata $metadata)
    {
        $extraMetadata = call_user_func($this->metadataCallback);
        if ($extraMetadata instanceof Metadata) {
            $metadata = $metadata->merge($extraMetadata);
        }
        return $metadata;
    }
}
