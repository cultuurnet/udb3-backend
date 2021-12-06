<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Contact;

use CultuurNet\UDB3\Model\ValueObject\Collection\Behaviour\IsStringArray;
use CultuurNet\UDB3\Model\ValueObject\Collection\Collection;

class TelephoneNumbers extends Collection
{
    use IsStringArray;

    /**
     * @param TelephoneNumber[] ...$values
     */
    public function __construct(TelephoneNumber ...$values)
    {
        parent::__construct(...$values);
    }
}
