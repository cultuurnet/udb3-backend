<?php

namespace CultuurNet\UDB3\CommandHandling;

use Broadway\Domain\Metadata;

trait ContextAwareTrait
{
    /**
     * @var Metadata
     */
    protected $metadata;

    /**
     * @param Metadata $metadata
     */
    public function setContext(Metadata $metadata = null)
    {
        $this->metadata = $metadata;
    }
}
