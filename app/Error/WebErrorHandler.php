<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Error;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblemFactory;
use CultuurNet\UDB3\Http\Response\ApiProblemJsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;

final class WebErrorHandler implements MiddlewareInterface
{
    private ErrorLogger $errorLogger;
    private bool $debugMode;

    public function __construct(ErrorLogger $errorLogger, bool $debugMode)
    {
        $this->errorLogger = $errorLogger;
        $this->debugMode = $debugMode;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            return $handler->handle($request);
        } catch (Throwable $e) {
            return $this->handle($request, $e);
        }
    }

    public function handle(ServerRequestInterface $request, Throwable $e): ApiProblemJsonResponse
    {
        $this->errorLogger->log($e);
        $problem = ApiProblemFactory::createFromThrowable($request, $e);
        if ($this->debugMode) {
            $problem->setDebugInfo(ContextExceptionConverterProcessor::convertThrowableToArray($e));
        }
        return new ApiProblemJsonResponse($problem);
    }
}
