<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\ApiProblem;

use Crell\ApiProblem\ApiProblem;
use Exception;

final class ApiProblemException extends Exception
{
    private ApiProblem $apiProblem;

    public function __construct(ApiProblem $apiProblem)
    {
        parent::__construct($apiProblem->getTitle(), $apiProblem->getStatus());
        $this->apiProblem = $apiProblem;
    }

    public function getApiProblem(): ApiProblem
    {
        return $this->apiProblem;
    }
}
