<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Completeness;

use CultuurNet\UDB3\Model\ValueObject\Collection\Behaviour\IsNotEmpty;
use CultuurNet\UDB3\Model\ValueObject\Collection\Collection;

final class Weights extends Collection
{
    use IsNotEmpty;

    public function __construct(Weight ...$weights)
    {
        $this->guardNotEmpty($weights);
        parent::__construct(...$weights);
    }

    public static function fromConfig(array $config): self
    {
        $weights = [];
        foreach ($config as $name => $value) {
            $weights[] = new Weight($name, $value);
        }
        return new Weights(...$weights);
    }
}
