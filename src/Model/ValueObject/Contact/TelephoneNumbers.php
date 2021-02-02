<?php

namespace CultuurNet\UDB3\Model\ValueObject\Contact;

use CultuurNet\UDB3\Model\ValueObject\Collection\Collection;

class TelephoneNumbers extends Collection
{
    /**
     * @param TelephoneNumber[] ...$values
     */
    public function __construct(TelephoneNumber ...$values)
    {
        parent::__construct(...$values);
    }
}
