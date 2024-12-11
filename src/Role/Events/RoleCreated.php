<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Role\Events;

use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;

final class RoleCreated extends AbstractEvent
{
    private string $name;

    final public function __construct(Uuid $uuid, string $name)
    {
        parent::__construct($uuid);
        $this->name = $name;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public static function deserialize(array $data): RoleCreated
    {
        return new static(
            new Uuid($data['uuid']),
            $data['name']
        );
    }

    public function serialize(): array
    {
        return parent::serialize() + [
            'name' => $this->name,
        ];
    }
}
