<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Text;

use InvalidArgumentException;

final class DescriptionShouldNotBeEmpty extends InvalidArgumentException
{
    public function __construct()
    {
        parent::__construct('Description should not be empty.');
    }
}
