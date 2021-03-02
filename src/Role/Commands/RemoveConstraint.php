<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Role\Commands;

use ValueObjects\Identity\UUID;
use CultuurNet\UDB3\ValueObject\SapiVersion;

class RemoveConstraint extends AbstractCommand
{
    /**
     * @var string
     */
    private $sapiVersion;


    public function __construct(
        UUID $uuid,
        SapiVersion $sapiVersion
    ) {
        parent::__construct($uuid);
        $this->sapiVersion = $sapiVersion->toNative();
    }


    public function getSapiVersion(): SapiVersion
    {
        return SapiVersion::fromNative($this->sapiVersion);
    }
}
