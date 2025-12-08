<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Verenigingsloket\Result;

final class VerenigingsloketConnectionResult
{
    public function __construct(private string $vcode, private string $url)
    {
    }

    public function getVcode(): string
    {
        return $this->vcode;
    }

    public function getUrl(): string
    {
        return $this->url;
    }
}
