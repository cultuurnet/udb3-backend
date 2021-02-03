<?php

namespace CultuurNet\Broadway\Domain;

use Broadway\Domain\DomainMessage;

class DomainMessageIsReplayed implements DomainMessageSpecificationInterface
{
    const METADATA_REPLAY_KEY = 'replayed';

    /**
     * @param DomainMessage $domainMessage
     * @return bool
     */
    public function isSatisfiedBy(DomainMessage $domainMessage)
    {
        $metadata = $domainMessage->getMetadata()->serialize();
        return isset($metadata[self::METADATA_REPLAY_KEY]) && $metadata[self::METADATA_REPLAY_KEY];
    }
}
