<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Price;

class Currency
{
    private string $code;

    public function __construct(string $code)
    {
        $this->code = $code;
    }

    public function getCode(): string
    {
        return $this->code;
    }
}
