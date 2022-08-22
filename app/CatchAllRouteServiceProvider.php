<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\Response\ApiProblemJsonResponse;
use League\Route\Http\Exception\MethodNotAllowedException;
use League\Route\Http\Exception\NotFoundException;
use League\Route\Router;
use Silex\Application;
use Silex\ServiceProviderInterface;
use Slim\Psr7\Factory\UriFactory;
use Symfony\Bridge\PsrHttpMessage\Factory\DiactorosFactory;
use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final class CatchAllRouteServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app): void
    {
    }

    public function boot(Application $app): void
    {
        $pathHasBeenRewrittenBefore = false;
        $originalRequest = null;

        // NOTE: THIS CATCH-ALL ROUTE HAS TO BE REGISTERED INSIDE boot() SO THAT (DYNAMICALLY GENERATED) OPTIONS ROUTES
        // FOR CORS GET REGISTERED FIRST BEFORE THIS ONE.
        // Matches any path that does not match a registered route, and rewrites it using a set of predefined pattern
        // replacements and sends an internal sub-request to try and match an existing route.
        // This makes it possible to support old endpoint names without having to register controllers/request handlers
        // twice.
        // If the sub-request does not return a response either, it will be converted to a PSR request and dispatched on
        // a new PSR router.
        $app->match(
            '/{path}',
            function (Request $request, string $path) use ($app, &$pathHasBeenRewrittenBefore, &$originalRequest) {
                $rewritePath = static function (string $originalPath): string {
                    $rewrites = [
                        // Pluralize /event and /place
                        '/^(\/)?(event|place)($|\/.*)/' => '${1}${2}s${3}',

                        // Convert known legacy camelCase resource/collection names to kebab-case
                        '/bookingAvailability/' => 'booking-availability',
                        '/bookingInfo/' => 'booking-info',
                        '/cardSystems/' => 'card-systems',
                        '/contactPoint/' => 'contact-point',
                        '/distributionKey/' => 'distribution-key',
                        '/majorInfo/' => 'major-info',
                        '/priceInfo/' => 'price-info',
                        '/subEvents/' => 'sub-events',
                        '/typicalAgeRange/' => 'typical-age-range',

                        // Convert old "calsum" path to "calendar-summary"
                        '/\/calsum/' => '/calendar-summary',

                        // Convert old "news_articles" path to "news-articles"
                        '/news_articles/' => 'news-articles',
                    ];
                    return preg_replace(array_keys($rewrites), array_values($rewrites), $originalPath);
                };

                if (!$pathHasBeenRewrittenBefore) {
                    // If the path has not been rewritten before, rewrite it and dispatch the request again to the Silex
                    // router. Note that the Silex router also requires us to append a trailing slash if it's missing,
                    // whereas the PSR router treats paths with or without trailing slash the same.
                    $rewrittenPath = $rewritePath($path);
                    $rewrittenPath = preg_replace('/^(.*)(?<!\/)$/', '${1}/', $rewrittenPath);
                    $pathHasBeenRewrittenBefore = true;
                    $originalRequest = $request;

                    // Create a new Request object with the rewritten path, because it's basically impossible to overwrite
                    // the path of an existing Request object even with initialize() or duplicate(). Approach copied from
                    // https://github.com/graze/silex-trailing-slash-handler/blob/1.x/src/TrailingSlashControllerProvider.php
                    $rewrittenRequest = Request::create(
                        $rewrittenPath,
                        $request->getMethod(),
                        [],
                        $request->cookies->all(),
                        $request->files->all(),
                        $request->server->all(),
                        $request->getContent()
                    );
                    $rewrittenRequest = $rewrittenRequest->duplicate(
                        $request->query->all(),
                        $request->request->all()
                    );
                    $rewrittenRequest->headers->replace($app['request']->headers->all());

                    // Handle the request with the rewritten path.
                    return $app->handle($rewrittenRequest, HttpKernelInterface::SUB_REQUEST);
                } else {
                    /** @var Router $router */
                    $router = $app[Router::class];
                    $psrRequest = (new DiactorosFactory())->createRequest($originalRequest);

                    // Always rewrite the request before dispatching the request on the PSR router.
                    // The only case that this could be a problem is if there is a route that is registered with an
                    // outdated name, but it makes the logic a lot easier. So we should just make sure to use the newer
                    // names when registering the routes on the new router.
                    $path = $psrRequest->getUri()->getPath();
                    $rewrittenPath = $rewritePath($path);
                    $rewrittenUri = (new UriFactory())->createUri($rewrittenPath);
                    $rewrittenPsrRequest = $psrRequest->withUri($rewrittenUri);

                    try {
                        $psrResponse = $router->handle($rewrittenPsrRequest);
                    } catch (NotFoundException $e) {
                        return new ApiProblemJsonResponse(ApiProblem::urlNotFound());
                    } catch (MethodNotAllowedException $e) {
                        $details = null;
                        $headers = $e->getHeaders();
                        $allowed = $headers['Allow'] ?? null;
                        if ($allowed !== null) {
                            $details = 'Allowed: ' . $allowed;
                        }
                        return new ApiProblemJsonResponse(ApiProblem::methodNotAllowed($details));
                    }

                    return (new HttpFoundationFactory())->createResponse($psrResponse);
                }
            }
        )->assert('path', '^.+$');
    }
}
