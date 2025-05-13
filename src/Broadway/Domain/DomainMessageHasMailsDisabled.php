<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Broadway\Domain;

use Broadway\Domain\DomainMessage;

class DomainMessageHasMailsDisabled implements DomainMessageSpecificationInterface
{
    public const METADATA_MAILS_DISABLED_KEY = 'disable_mails';

    public function isSatisfiedBy(DomainMessage $domainMessage): bool
    {
        $metadata = $domainMessage->getMetadata()->serialize();
        return isset($metadata[self::METADATA_MAILS_DISABLED_KEY]) && $metadata[self::METADATA_MAILS_DISABLED_KEY];
    }
}
