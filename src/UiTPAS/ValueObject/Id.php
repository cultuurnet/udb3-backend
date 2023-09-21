<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\UiTPAS\ValueObject;

use CultuurNet\UDB3\Model\ValueObject\String\Behaviour\IsNotEmpty;
use CultuurNet\UDB3\Model\ValueObject\String\Behaviour\Trims;

final class Id
{
    use IsNotEmpty;
    use Trims;

    private string $value;

    public function __construct(string $value)
    {
        $value = $this->trim($value);
        $this->guardNotEmpty($value);
        $this->value = $value;
    }

    public function toNative(): string
    {
        return $this->value;
    }
}
