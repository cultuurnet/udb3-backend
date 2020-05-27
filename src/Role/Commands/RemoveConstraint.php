<?php

namespace CultuurNet\UDB3\Role\Commands;

use ValueObjects\Identity\UUID;
use CultuurNet\UDB3\ValueObject\SapiVersion;

class RemoveConstraint extends AbstractCommand
{
    /**
     * @var string
     */
    private $sapiVersion;

    /**
     * @param UUID $uuid
     * @param SapiVersion $sapiVersion
     */
    public function __construct(
        UUID $uuid,
        SapiVersion $sapiVersion
    ) {
        parent::__construct($uuid);
        $this->sapiVersion = $sapiVersion->toNative();
    }

    /**
     * @return SapiVersion
     */
    public function getSapiVersion(): SapiVersion
    {
        return SapiVersion::fromNative($this->sapiVersion);
    }
}
