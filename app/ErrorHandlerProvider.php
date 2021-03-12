<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex;

use Crell\ApiProblem\ApiProblem;
use CultureFeed_Exception;
use CultureFeed_HttpException;
use CultuurNet\UDB3\Deserializer\DataValidationException;
use CultuurNet\UDB3\EntityNotFoundException;
use CultuurNet\UDB3\HttpFoundation\Response\ApiProblemJsonResponse;
use CultuurNet\UDB3\Security\CommandAuthorizationException;
use Exception;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Respect\Validation\Exceptions\GroupedValidationException;
use Silex\Application;
use Silex\ServiceProviderInterface;

class ErrorHandlerProvider implements ServiceProviderInterface
{
    public function register(Application $app): void
    {
        $app[PsrLoggerErrorHandler::class] = $app::share(
            function (): PsrLoggerErrorHandler {
                $logger = new Logger('logger.errors');
                $logger->pushHandler(new StreamHandler(__DIR__ . '/../log/error.log'));
                return new PsrLoggerErrorHandler($logger);
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
            function (CommandAuthorizationException $e) {
                $problem = $this->createNewApiProblem($e);
                $problem->setStatus(401);
                return new ApiProblemJsonResponse($problem);
            }
        );

        $app->error(
            function (CultureFeed_Exception $e) use ($app) {
                $app[PsrLoggerErrorHandler::class]->handle($e);
                $app[SentryErrorHandler::class]->handle($e);
                $problem = $this->createNewApiProblemFromCultureFeedException($e);
                return new ApiProblemJsonResponse($problem);
            }
        );

        $app->error(
            function (CultureFeed_HttpException $e) use ($app) {
                $app[PsrLoggerErrorHandler::class]->handle($e);
                $app[SentryErrorHandler::class]->handle($e);
                $problem = $this->createNewApiProblemFromCultureFeedException($e);
                return new ApiProblemJsonResponse($problem);
            }
        );

        $app->error(
            function (Exception $e) use ($app) {
                $app[PsrLoggerErrorHandler::class]->handle($e);
                $app[SentryErrorHandler::class]->handle($e);
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

    /**
     * Returns a new ApiProblem just like createNewApiProblem(), but also removes some internal debug info pertaining to
     * CultureFeed from the error message.
     *
     * E.g. "event is not known in uitpas URL CALLED: https://acc.uitid.be/uitid/rest/uitpas/cultureevent/..." (etc)
     * becomes "event is not known in uitpas ".
     * The trailing space could easily be removed but it's there for backward compatibility with systems that might have
     * implemented a comparison on the error message when this was introduced in udb3-uitpas-service in the past.
     */
    private function createNewApiProblemFromCultureFeedException(Exception $exception): ApiProblem
    {
        $apiProblem = $this->createNewApiProblem($exception);

        $title = $apiProblem->getTitle();

        // Remove "URL CALLED" and everything after it.
        $formattedTitle = preg_replace('/URL CALLED.*/', '', $title);

        $clonedApiProblem = clone $apiProblem;
        $clonedApiProblem->setTitle($formattedTitle);
        return $clonedApiProblem;
    }

    public function boot(Application $app): void
    {
    }
}
