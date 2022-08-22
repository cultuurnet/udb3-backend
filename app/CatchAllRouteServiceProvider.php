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
        // replacements and sends an internal sub-request to try and match an existing route. If the sub-request does
        // not return a response either an error response will be returned.
        // This makes it possible to support old endpoint names without having to register controllers/request handlers
        // twice. When we have a router with support for PSR-15 middlewares, we should refactor this URL rewriting to a
        // PSR-15 middleware instead.
        $app->match(
            '/{path}',
            function (Request $request, string $path) use ($app, &$pathHasBeenRewrittenBefore, &$originalRequest) {
                $rewritePath = static function (string $originalPath): string {
                    $rewrites = [
                        // Pluralize /event and /place
                        '/^(event|place)($|\/.*)/' => '${1}s${2}',

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

                        // Add trailing slash if missing
                        '/^(.*)(?<!\/)$/' => '${1}/',
                    ];
                    return preg_replace(array_keys($rewrites), array_values($rewrites), $originalPath);
                };

                if ($pathHasBeenRewrittenBefore) {
                    /** @var Router $router */
                    $router = $app[Router::class];
                    $psrRequest = (new DiactorosFactory())->createRequest($originalRequest);

                    try {
                        $psrResponse = $router->handle($psrRequest);
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

                $rewrittenPath = $rewritePath($path);
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
            }
        )->assert('path', '^.+$');
    }
}
