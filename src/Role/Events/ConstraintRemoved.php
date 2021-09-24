<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Role\Events;

use CultuurNet\UDB3\ValueObject\SapiVersion;
use ValueObjects\Identity\UUID;

final class ConstraintRemoved extends AbstractEvent
{
    private SapiVersion $sapiVersion;

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
            SapiVersion::V3()
        );
    }

    public function serialize(): array
    {
        return parent::serialize() + [
                'sapiVersion' => $this->sapiVersion->toNative(),
            ];
    }
}
