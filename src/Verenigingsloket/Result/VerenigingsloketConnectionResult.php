<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Verenigingsloket\Result;

use CultuurNet\UDB3\Verenigingsloket\Enum\VerenigingsloketConnectionStatus;

final class VerenigingsloketConnectionResult
{
    public function __construct(private string $vcode, private string $url, private string $relationId, private VerenigingsloketConnectionStatus $status)
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

    public function getRelationId(): string
    {
        return $this->relationId;
    }

    public function getStatus(): VerenigingsloketConnectionStatus
    {
        return $this->status;
    }
}
