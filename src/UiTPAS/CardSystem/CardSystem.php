<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\UiTPAS\CardSystem;

use CultuurNet\UDB3\UiTPAS\ValueObject\Id;
use ValueObjects\StringLiteral\StringLiteral;

class CardSystem
{
    /**
     * @var Id
     */
    private $id;

    /**
     * @var StringLiteral
     */
    private $name;


    public function __construct(
        Id $id,
        StringLiteral $name
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
        return $this->name;
    }
}
