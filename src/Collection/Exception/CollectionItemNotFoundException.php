<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Collection\Exception;

class CollectionItemNotFoundException extends \Exception
{
    public function __construct()
    {
        parent::__construct('The specified item was not found in the collection.', 404);
    }
}
