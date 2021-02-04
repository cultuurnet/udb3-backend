<?php

namespace CultuurNet\UDB3\Collection\Exception;

class CollectionKeyNotFoundException extends \Exception
{
    public function __construct(string $key)
    {
        parent::__construct(sprintf('The specified key "%s" was not found in the collection.', $key), 404);
    }
}
