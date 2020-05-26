<?php

namespace CultuurNet\UDB3\Role\Commands;

use ValueObjects\Identity\UUID;
use CultuurNet\UDB3\Role\ValueObjects\Query;
use CultuurNet\UDB3\ValueObject\SapiVersion;

class AddConstraint extends AbstractCommand
{
    /**
     * @var string
     */
    private $sapiVersion;

    /**
     * @var Query
     */
    private $query;

    /**
     * CreateConstraint constructor.
     * @param UUID $uuid
     * @param SapiVersion $sapiVersion
     * @param Query $query
     */
    public function __construct(
        UUID $uuid,
        SapiVersion $sapiVersion,
        Query $query
    ) {
        parent::__construct($uuid);
        $this->sapiVersion = $sapiVersion->toNative();
        $this->query = $query;
    }

    /**
     * @return SapiVersion
     */
    public function getSapiVersion(): SapiVersion
    {
        return SapiVersion::fromNative($this->sapiVersion);
    }

    /**
     * @return Query
     */
    public function getQuery(): Query
    {
        return $this->query;
    }
}
