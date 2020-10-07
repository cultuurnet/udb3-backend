<?php

namespace CultuurNet\UDB3\Silex;

use Crell\ApiProblem\ApiProblem;
use CultuurNet\Deserializer\DataValidationException;
use CultuurNet\UDB3\EntityNotFoundException;
use CultuurNet\UDB3\HttpFoundation\Response\ApiProblemJsonResponse;
use CultuurNet\UDB3\Security\CommandAuthorizationException;
use Exception;
use Respect\Validation\Exceptions\GroupedValidationException;
use Silex\Application;
use Silex\ServiceProviderInterface;

class ErrorHandlerProvider implements ServiceProviderInterface
{
    public function register(Application $app): void
    {
        $app->error(
            function (GroupedValidationException $e) {
                $problem = $this->createNewApiProblem($e);
                $problem['validation_messages'] = $e->getMessages();
                return new ApiProblemJsonResponse($problem);
            }
        );

        $app->error(
            function (DataValidationException $e) {
                $problem = new ApiProblem('Invalid payload.');
                $problem['validation_messages'] = $e->getValidationMessages();
                return new ApiProblemJsonResponse($problem);
            }
        );

        $app->error(
            function (EntityNotFoundException $e) {
                $problem = $this->createNewApiProblem($e);
                return new ApiProblemJsonResponse($problem);
            }
        );

        $app->error(
            function (CommandAuthorizationException $e) {
                $problem = $this->createNewApiProblem($e);
                $problem->setStatus(401);
                return new ApiProblemJsonResponse($problem);
            }
        );

        $app->error(
            function (Exception $e) {
                $problem = $this->createNewApiProblem($e);
                return new ApiProblemJsonResponse($problem);
            }
        );
    }

    private function createNewApiProblem(Exception $e): ApiProblem
    {
        $problem = new ApiProblem($e->getMessage());
        $problem->setStatus($e->getCode() ?: ApiProblemJsonResponse::HTTP_BAD_REQUEST);
        return $problem;
    }

    public function boot(Application $app): void
    {
    }
}
