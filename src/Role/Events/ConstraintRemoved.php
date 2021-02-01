<?php

namespace CultuurNet\UDB3\Role\Events;

use CultuurNet\UDB3\ValueObject\SapiVersion;
use ValueObjects\Identity\UUID;

final class ConstraintRemoved extends AbstractEvent
{
    /**
     * @var SapiVersion
     */
    private $sapiVersion;

    final public function __construct(
        UUID $uuid,
        SapiVersion $sapiVersion
    ) {
        parent::__construct($uuid);
        $this->sapiVersion = $sapiVersion;
    }

    public function getSapiVersion(): SapiVersion
    {
        return $this->sapiVersion;
    }

    public static function deserialize(array $data): ConstraintRemoved
    {
        return new static(
            new UUID($data['uuid']),
            SapiVersion::fromNative($data['sapiVersion'])
        );
    }

    public function serialize(): array
    {
        return parent::serialize() + array(
                'sapiVersion' => $this->sapiVersion->toNative(),
            );
    }
}
