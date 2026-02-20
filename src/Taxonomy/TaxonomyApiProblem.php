<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Taxonomy;

use Exception;

final class TaxonomyApiProblem extends Exception
{
    public function __construct(string $message)
    {
        parent::__construct($message);
    }
}
