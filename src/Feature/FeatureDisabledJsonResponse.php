<?php

namespace CultuurNet\UDB3\Silex\Feature;

use Crell\ApiProblem\ApiProblem;
use CultuurNet\UDB3\HttpFoundation\Response\ApiProblemJsonResponse;
use Symfony\Component\HttpFoundation\Response;

class FeatureDisabledJsonResponse extends ApiProblemJsonResponse
{
    public function __construct()
    {
        $apiProblem = new ApiProblem('Feature is disabled on this installation.');
        $apiProblem->setStatus(Response::HTTP_SERVICE_UNAVAILABLE);

        parent::__construct($apiProblem);
    }
}
