<?php

namespace CultuurNet\UDB3\Silex;

use Crell\ApiProblem\ApiProblem;
use CultuurNet\Deserializer\DataValidationException;
use CultuurNet\UDB3\EntityNotFoundException;
use CultuurNet\UDB3\HttpFoundation\Response\ApiProblemJsonResponse;
use CultuurNet\UDB3\Variations\Command\ValidationException;
use Respect\Validation\Exceptions\GroupedValidationException;
use Silex\Application;
use Silex\ServiceProviderInterface;

class ErrorHandlerProvider implements ServiceProviderInterface
{
    /**
     * @inheritdoc
     */
    public function register(Application $app)
    {
        $app->error(
            function (ValidationException $e) {
                $problem = $this->createNewApiProblem($e);
                $problem['validation_messages'] = $e->getErrors();
                return new ApiProblemJsonResponse($problem);
            }
        );

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
            function (\Exception $e) {
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
