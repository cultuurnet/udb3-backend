<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\ConvertsToApiProblem;
use Exception;

final class FaqIdDoesNotExist extends Exception implements ConvertsToApiProblem
{
    public function __construct(string $faqId)
    {
        parent::__construct("FAQ with id '$faqId' does not exist.");
    }

    public function toApiProblem(): ApiProblem
    {
        return ApiProblem::bodyInvalidDataWithDetail($this->getMessage());
    }
}
