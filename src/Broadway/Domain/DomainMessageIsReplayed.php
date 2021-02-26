<?php

namespace CultuurNet\UDB3\Broadway\Domain;

use Broadway\Domain\DomainMessage;

class DomainMessageIsReplayed implements DomainMessageSpecificationInterface
{
    public const METADATA_REPLAY_KEY = 'replayed';

    /**
     * @return bool
     */
    public function isSatisfiedBy(DomainMessage $domainMessage)
    {
        $metadata = $domainMessage->getMetadata()->serialize();
        return isset($metadata[self::METADATA_REPLAY_KEY]) && $metadata[self::METADATA_REPLAY_KEY];
    }
}
