<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Collection\Mock;

use CultuurNet\UDB3\Collection\AbstractCollection;

final class FooCollection extends AbstractCollection
{
    /**
     * @inheritdoc
     */
    protected function getValidObjectType()
    {
        return Foo::class;
    }
}
