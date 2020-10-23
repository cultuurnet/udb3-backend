<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Popularity;

class Popularity
{
    /**
     * @var int
     */
    private $value;

    public function __construct(int $value)
    {
        $this->value = $value;
    }

    public function toNative(): int
    {
        return $this->value;
    }
}
