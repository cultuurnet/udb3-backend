<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Collection\Mock;

class Foo
{
    protected int $id;

    protected string $name;

    public function __construct(int $id, string $name)
    {
        $this->id = $id;
        $this->name = $name;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }
}
