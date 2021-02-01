<?php

namespace CultuurNet\UDB3\Role\Events;

use CultuurNet\UDB3\Role\ValueObjects\Query;
use CultuurNet\UDB3\ValueObject\SapiVersion;
use ValueObjects\Identity\UUID;

abstract class AbstractConstraintEvent extends AbstractEvent
{
    /**
     * @var SapiVersion
     */
    private $sapiVersion;

    /**
     * @var Query
     */
    private $query;

    /**
     * AbstractPermissionEvent constructor.
     * @param UUID $uuid
     * @param SapiVersion $sapiVersion
     * @param Query $query
     */
    final public function __construct(
        UUID $uuid,
        SapiVersion $sapiVersion,
        Query $query
    ) {
        parent::__construct($uuid);
        $this->sapiVersion = $sapiVersion;
        $this->query = $query;
    }

    public function getSapiVersion(): SapiVersion
    {
        return $this->sapiVersion;
    }

    public function getQuery(): Query
    {
        return $this->query;
    }

    public static function deserialize(array $data): AbstractConstraintEvent
    {
        return new static(
            new UUID($data['uuid']),
            SapiVersion::fromNative($data['sapiVersion']),
            new Query($data['query'])
        );
    }

    public function serialize(): array
    {
        return parent::serialize() + array(
            'sapiVersion' => $this->sapiVersion->toNative(),
            'query' => $this->query->toNative(),
        );
    }
}
