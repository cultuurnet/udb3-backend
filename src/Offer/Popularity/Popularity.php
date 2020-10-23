<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Popularity;

use InvalidArgumentException;

class Popularity
{
    /**
     * @var int
     */
    private $value;

    public function __construct(int $value)
    {
        if ($value < 0) {
            throw new InvalidArgumentException('Popularity can\'t be smaller than zero.');
        }
        $this->value = $value;
    }

    public function toNative(): int
    {
        return $this->value;
    }
}
