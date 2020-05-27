<?php
/**
 * @file
 */

namespace CultuurNet\UDB3\CommandHandling;

use Broadway\Domain\Metadata;

interface ContextAwareInterface
{
    public function setContext(Metadata $context = null);
}
