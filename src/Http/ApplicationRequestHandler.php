<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\Response\ApiProblemJsonResponse;
use League\Route\Http\Exception\MethodNotAllowedException;
use League\Route\Http\Exception\NotFoundException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Factory\UriFactory;
use Throwable;

/**
 * Request handler that handles any incoming request by delegating it to a router that implements
 * RequestHandlerInterface.
 *
 * Adds some extra sugar like rewriting the paths of incoming requests if needed (to support legacy URLs), and
 * converting exceptions/errors into API problems.
 */
final class ApplicationRequestHandler implements RequestHandlerInterface
{
    private RequestHandlerInterface $router;

    public function __construct(RequestHandlerInterface $router)
    {
        $this->router = $router;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $request = $this->rewriteRequestUri($request);

        try {
            return $this->router->handle($request);
        } catch (Throwable $e) {
            return $this->handleError($e);
        }
    }

    private function rewriteRequestUri(ServerRequestInterface $request): ServerRequestInterface
    {
        $path = $request->getUri()->getPath();
        $rewrittenPath = (new LegacyPathRewriter())->rewrite($path);
        $rewrittenUri = (new UriFactory())->createUri($rewrittenPath);
        return $request->withUri($rewrittenUri);
    }

    private function handleError(Throwable $e): ResponseInterface
    {
        switch (true) {
            case $e instanceof NotFoundException:
                return new ApiProblemJsonResponse(ApiProblem::urlNotFound());

            case $e instanceof MethodNotAllowedException:
                $details = null;
                $headers = $e->getHeaders();
                $allowed = $headers['Allow'] ?? null;
                if ($allowed !== null) {
                    $details = 'Allowed: ' . $allowed;
                }
                return new ApiProblemJsonResponse(ApiProblem::methodNotAllowed($details));

            default:
                throw $e;
        }
    }
}
