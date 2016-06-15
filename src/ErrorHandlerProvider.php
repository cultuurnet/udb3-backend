<?php

namespace CultuurNet\UDB3\Silex;

use Crell\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Symfony\HttpFoundation\ApiProblemJsonResponse;
use CultuurNet\UDB3\Variations\Command\ValidationException;
use Silex\Application;
use Silex\ServiceProviderInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use ValueObjects\Exception\InvalidNativeArgumentException;

class ErrorHandlerProvider implements ServiceProviderInterface
{
    /**
     * @inheritdoc
     */
    public function register(Application $app)
    {
        $provider = $this;

        $app->error(
            function (ValidationException $e) use ($provider) {
                $problem = new ApiProblem($e->getMessage());
                $problem['validation_messages'] = $e->getErrors();
                $problem->setStatus($e->getCode() ? $e->getCode() : ApiProblemJsonResponse::HTTP_BAD_REQUEST);
                return new ApiProblemJsonResponse($problem);
            }
        );

        $app->error(
            function (\Exception $e) use ($provider) {
                $problem = new ApiProblem($e->getMessage());
                $problem->setStatus($e->getCode() ? $e->getCode() : ApiProblemJsonResponse::HTTP_BAD_REQUEST);
                return new ApiProblemJsonResponse($problem);
            }
        );
    }

    /**
     * @inheritdoc
     */
    public function boot(Application $app)
    {

    }
}
