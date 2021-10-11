<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\Response\ApiProblemJsonResponse;
use Silex\Application;
use Silex\ServiceProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final class LegacyRoutesServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app): void
    {
    }

    public function boot(Application $app): void
    {
        $pathHasBeenRewritten = false;

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
            function (Request $originalRequest, string $path) use ($app, &$pathHasBeenRewritten) {
                if ($pathHasBeenRewritten) {
                    return new ApiProblemJsonResponse(ApiProblem::urlNotFound());
                }

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

                    // Add trailing slash if missing
                    '/^(.*)(?<!\/)$/' => '${1}/',
                ];
                $rewrittenPath = preg_replace(array_keys($rewrites), array_values($rewrites), $path);

                $pathHasBeenRewritten = true;

                // Create a new Request object with the rewritten path, because it's basically impossible to overwrite
                // the path of an existing Request object even with initialize() or duplicate(). Approach copied from
                // https://github.com/graze/silex-trailing-slash-handler/blob/1.x/src/TrailingSlashControllerProvider.php
                $request = Request::create(
                    $rewrittenPath,
                    $originalRequest->getMethod(),
                    [],
                    $originalRequest->cookies->all(),
                    $originalRequest->files->all(),
                    $originalRequest->server->all(),
                    $originalRequest->getContent()
                );
                $request = $request->duplicate(
                    $originalRequest->query->all(),
                    $originalRequest->request->all()
                );
                $request->headers->replace($app['request']->headers->all());

                // Handle the request with the rewritten path.
                return $app->handle($request, HttpKernelInterface::SUB_REQUEST);
            }
        )->assert('path', '^.+$');

        // Middleware that rewrites old query parameter names to new query parameter names.
        // Since the replacements are globally, make sure that the parameter name that you want to rename is not used in
        // other places (or update all usages)!
        $app->before(
            function (Request $request) {
                $renameQueryParameters = [
                    // Fix casing of incorrectly cased words
                    'timeZone' => 'timezone',
                ];

                foreach ($renameQueryParameters as $oldName => $newName) {
                    if ($request->query->has($oldName)) {
                        $value = $request->query->get($oldName);
                        $request->query->set($newName, $value);
                        $request->query->remove($oldName);
                    }
                }
            }
        );
    }
}
