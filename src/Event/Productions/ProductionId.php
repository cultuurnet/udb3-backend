<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Productions;

use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;

final class ProductionId
{
    private string $id;

    private function __construct(string $id)
    {
        $this->id = $id;
    }

    public static function generate(): self
    {
        return new self(Uuid::uuid4()->toString());
    }

    public static function fromNative(string $id): self
    {
        return new self($id);
    }

    public function toNative(): string
    {
        return $this->id;
    }

    public function equals(ProductionId $other): bool
    {
        return $this->id === $other->id;
    }
}
