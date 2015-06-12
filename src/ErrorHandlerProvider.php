<?php
/**
 * @file
 */

namespace CultuurNet\UDB3\Silex;

use Crell\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Silex\HttpFoundation\ApiProblemJsonResponse;
use CultuurNet\UDB3\Variations\Command\ValidationException;
use Silex\Application;
use Silex\ServiceProviderInterface;

class ErrorHandlerProvider implements ServiceProviderInterface
{
    /**
     * @inheritdoc
     */
    public function register(Application $app)
    {
        $provider = $this;

        $app->error(function (ValidationException $e) use ($provider) {
            $problem = new ApiProblem($e->getMessage());
            $problem['validation_messages'] = $e->getErrors();

            return $provider->createBadRequestResponse($problem);
        });
    }

    private function createBadRequestResponse(ApiProblem $problem)
    {
        $status = ApiProblemJsonResponse::HTTP_BAD_REQUEST;
        $problem->setStatus($status);

        $response = new ApiProblemJsonResponse($problem);

        return $response;
    }

    /**
     * @inheritdoc
     */
    public function boot(Application $app)
    {

    }
}
