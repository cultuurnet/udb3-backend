<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\FastRead;

use Closure;
use CultuurNet\UDB3\Http\Request\QueryParameters;
use CultuurNet\UDB3\Http\Request\RouteParameters;
use CultuurNet\UDB3\Http\Response\JsonLdResponse;
use CultuurNet\UDB3\ReadModel\DocumentDoesNotExist;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Serves GET /{events|places}/{id}/ (with only the includeMetadata/embedUitpasPrices
 * query params, no Accept: text/turtle) directly from FastOfferJsonDocumentReader instead
 * of letting the router resolve GetDetailRequestHandler -> OfferJsonDocumentReadRepository
 * -> the container-decorated 'event_jsonld_repository'/'place_jsonld_repository'.
 *
 * This has to run as an early global middleware, NOT inside GetDetailRequestHandler
 * itself: the container resolves the handler's full constructor dependency graph
 * (including the ~10-decorator offer repository chain) BEFORE handle() is ever called,
 * as part of matching/invoking the route. By the time handle() would run, the expensive
 * work this is meant to skip has already happened. Short-circuiting here, before
 * $handler->handle($request) is called, is what actually avoids it.
 *
 * $readerFactory MUST stay a lazy closure, not a resolved FastOfferJsonDocumentReader:
 * global middlewares are all resolved eagerly in
 * PsrRouterServiceProvider::registerMiddlewares(), which runs on every request regardless
 * of route (Router::class is built unconditionally in web/index.php). Injecting an
 * already-built reader here would resolve its whole leaf-dependency graph (permission
 * voter, contributor repository, term repository, ...) on every request to the entire
 * app, not just eligible offer-detail GETs - confirmed via profiling: doing so inflated
 * registerMiddlewares() from ~5% to ~35% of total request cost, for ALL routes. Calling
 * the factory only after isEligible() passes keeps that cost scoped to the requests that
 * actually benefit from it.
 *
 * On anything other than a genuine cache miss (DocumentDoesNotExist, which matches the
 * normal path's own 404 behaviour), this falls back to the normal $handler->handle()
 * rather than risk serving an incorrect response - a bug here should cost speed, not
 * correctness.
 *
 * See claude/read-side-performance-investigation.md for the investigation and
 * measurements behind this, and FastOfferJsonDocumentReader for the decorator chain.
 */
final class FastReadMiddleware implements MiddlewareInterface
{
    private const ALLOWED_QUERY_PARAMS = ['includeMetadata', 'embedUitpasPrices'];

    /** @var Closure(): \CultuurNet\UDB3\Offer\ReadModel\JSONLD\FastRead\FastOfferJsonDocumentReader */
    private Closure $readerFactory;
    private LoggerInterface $logger;

    public function __construct(Closure $readerFactory, LoggerInterface $logger)
    {
        $this->readerFactory = $readerFactory;
        $this->logger = $logger;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!$this->isEligible($request)) {
            return $handler->handle($request);
        }

        $routeParameters = new RouteParameters($request);
        $queryParameters = new QueryParameters($request);

        try {
            $reader = ($this->readerFactory)();
            $document = $reader->fetch(
                $routeParameters->getOfferType(),
                $routeParameters->getOfferId(),
                $queryParameters->getAsBoolean('includeMetadata')
            );
        } catch (DocumentDoesNotExist $exception) {
            // Same outcome as the normal path on a cache miss: let it propagate up to
            // the same WebErrorHandler that turns it into a 404 ApiProblem.
            throw $exception;
        } catch (Throwable $exception) {
            // Anything unexpected: don't risk serving a wrong/broken response for a bug
            // in this fast path, fall back to the normal (slower, known-correct) path.
            $this->logger->warning(
                'FastReadMiddleware failed, falling back to the normal read path',
                ['exception' => $exception]
            );
            return $handler->handle($request);
        }

        if (!$queryParameters->getAsBoolean('embedUitpasPrices')) {
            $document = $this->removeUiTPASPrices($document);
        }

        return new JsonLdResponse($document->getAssocBody());
    }

    private function isEligible(ServerRequestInterface $request): bool
    {
        if ($request->getMethod() !== 'GET') {
            return false;
        }

        if ($request->getHeaderLine('Accept') === 'text/turtle') {
            return false;
        }

        $routeParameters = new RouteParameters($request);
        if (!$routeParameters->hasOfferType() || !$routeParameters->hasOfferId()) {
            return false;
        }

        foreach (array_keys($request->getQueryParams()) as $queryParam) {
            if (!in_array($queryParam, self::ALLOWED_QUERY_PARAMS, true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Copied verbatim from GetDetailRequestHandler::removeUiTPASPrices() - including its
     * use of array_filter() without array_values(), which is what the normal path does too.
     */
    private function removeUiTPASPrices(JsonDocument $jsonDocument): JsonDocument
    {
        return $jsonDocument->applyAssoc(
            function (array $json) {
                if (!isset($json['priceInfo'])) {
                    return $json;
                }
                $json['priceInfo'] = array_filter(
                    $json['priceInfo'],
                    static fn (array $price) => $price['category'] !== 'uitpas'
                );
                return $json;
            }
        );
    }
}
