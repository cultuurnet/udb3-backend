<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\UiTPAS\CardSystem;

use CultuurNet\UDB3\UiTPAS\ValueObject\Id;

class CardSystem
{
    /**
     * @var Id
     */
    private $id;

    private string $name;


    public function __construct(
        Id $id,
        string $name
    ) {
        $this->id = $id;
        $this->name = $name;
    }

    /**
     * @return Id
     */
    public function getId()
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }
}
