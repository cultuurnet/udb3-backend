<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventListener;

use CultuurNet\UDB3\StringLiteral;

class ClassNameEventSpecification implements EventSpecification
{
    private array $classNames;

    public function __construct(StringLiteral ...$classNames)
    {
        $this->classNames = $classNames;
    }

    public function matches(object $event): bool
    {
        foreach ($this->classNames as $className) {
            if (get_class($event) === $className->toNative()) {
                return true;
            }
        }

        return false;
    }
}
