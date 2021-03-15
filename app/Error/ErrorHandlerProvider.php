<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Error;

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
use Sentry\Monolog\Handler as SentryHandler;
use Sentry\State\HubInterface;
use Silex\Application;
use Silex\ServiceProviderInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;

class ErrorHandlerProvider implements ServiceProviderInterface
{
    private const ERRORS_EXCLUDED_FROM_LOGS = [
        EntityNotFoundException::class,
        CommandAuthorizationException::class,
        NotFoundHttpException::class,
        MethodNotAllowedException::class,
        DataValidationException::class,
        GroupedValidationException::class,
    ];

    public function register(Application $app): void
    {
        $app[ErrorHandler::class] = $app::share(
            function (Application $app): ErrorHandler {
                $logger = new Logger('logger.errors');
                $logger->pushHandler(new StreamHandler(__DIR__ . '/../log/error.log'));
                $logger->pushHandler(new SentryHandler($app[HubInterface::class], Logger::ERROR));
                return new PsrLoggerErrorHandler($logger);
            }
        );

        $app->error(
            function (Exception $e) use ($app) {
                if (!in_array(get_class($e), self::ERRORS_EXCLUDED_FROM_LOGS)) {
                    $app[ErrorHandler::class]->handle($e);
                }

                $problem = $this->createNewApiProblem($e);
                return new ApiProblemJsonResponse($problem);
            }
        );
    }

    private function createNewApiProblem(Exception $e): ApiProblem
    {
        if ($e instanceof CultureFeed_Exception || $e instanceof CultureFeed_HttpException) {
            return $this->createNewApiProblemFromCultureFeedException($e);
        }

        $problem = new ApiProblem($e->getMessage());
        $problem->setStatus($e->getCode() ?: ApiProblemJsonResponse::HTTP_BAD_REQUEST);

        if ($e instanceof DataValidationException) {
            $problem->setTitle('Invalid payload.');
            $problem['validation_messages'] = $e->getValidationMessages();
        }

        if ($e instanceof GroupedValidationException) {
            $problem['validation_messages'] = $e->getMessages();
        }

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
