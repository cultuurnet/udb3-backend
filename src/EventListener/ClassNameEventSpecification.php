<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventListener;

class ClassNameEventSpecification implements EventSpecification
{
    private array $classNames;

    public function __construct(string ...$classNames)
    {
        $this->classNames = $classNames;
    }

    public function matches(object $event): bool
    {
        return in_array(get_class($event), $this->classNames, true);
    }
}
