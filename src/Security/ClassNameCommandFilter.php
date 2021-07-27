<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Security;

use ValueObjects\StringLiteral\StringLiteral;

class ClassNameCommandFilter implements CommandFilterInterface
{
    private $classNames;

    public function __construct(StringLiteral ...$classNames)
    {
        $this->classNames = $classNames;
    }

    /**
     * @inheritdoc
     */
    public function matches(AuthorizableCommandInterface $command)
    {
        foreach ($this->classNames as $className) {
            if (get_class($command) === $className->toNative()) {
                return true;
            }
        }

        return false;
    }
}
