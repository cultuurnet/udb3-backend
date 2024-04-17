<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\RDF;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\ConvertsToApiProblem;
use Exception;

final class JsonDataCouldNotBeConverted extends Exception implements ConvertsToApiProblem
{
    public function __construct(string $message)
    {
        parent::__construct($message);
    }

    public function toApiProblem(): ApiProblem
    {
        return ApiProblem::invalidJsonDataForRdfCreation($this->message);
    }
}
