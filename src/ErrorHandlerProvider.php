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
                $problem = $this->createNewApiProblem($e);
                $problem['validation_messages'] = $e->getErrors();
                return new ApiProblemJsonResponse($problem);
            }
        );

        $app->error(
            function (\Exception $e) use ($provider) {
                $problem = $this->createNewApiProblem($e);
                return new ApiProblemJsonResponse($problem);
            }
        );
    }

    /**
     * @param \Exception $e
     * @return ApiProblem
     */
    protected function createNewApiProblem(\Exception $e)
    {
        $problem = new ApiProblem($e->getMessage());
        $problem->setStatus($e->getCode() ? $e->getCode() : ApiProblemJsonResponse::HTTP_BAD_REQUEST);
        return $problem;
    }

    /**
     * @inheritdoc
     */
    public function boot(Application $app)
    {

    }
}
