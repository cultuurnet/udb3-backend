<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Web;

use CultuurNet\UDB3\Model\ValueObject\Collection\Behaviour\IsStringArray;
use CultuurNet\UDB3\Model\ValueObject\Collection\Collection;

class EmailAddresses extends Collection
{
    use IsStringArray;

    /**
     * @param EmailAddress[] ...$values
     */
    public function __construct(EmailAddress ...$values)
    {
        parent::__construct(...$values);
    }
}
