<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\UiTPAS\CardSystem;

use CultuurNet\UDB3\UiTPAS\ValueObject\Id;
use CultuurNet\UDB3\StringLiteral;

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

    /**
     * @return StringLiteral
     */
    public function getName()
    {
        return new StringLiteral($this->name);
    }
}
