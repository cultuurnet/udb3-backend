<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Role\Events;

use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\StringLiteral;

final class RoleCreated extends AbstractEvent
{
    /**
     * @var StringLiteral
     */
    private $name;

    final public function __construct(UUID $uuid, StringLiteral $name)
    {
        parent::__construct($uuid);
        $this->name = $name;
    }

    public function getName(): StringLiteral
    {
        return $this->name;
    }

    public static function deserialize(array $data): RoleCreated
    {
        return new static(
            new UUID($data['uuid']),
            new StringLiteral($data['name'])
        );
    }

    public function serialize(): array
    {
        return parent::serialize() + [
            'name' => $this->name->toNative(),
        ];
    }
}
