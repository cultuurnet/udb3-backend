<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Collection\Mock;

use CultuurNet\UDB3\Collection\AbstractCollection;

final class FooCollection extends AbstractCollection
{
    protected function getValidObjectType(): string
    {
        return Foo::class;
    }
}
