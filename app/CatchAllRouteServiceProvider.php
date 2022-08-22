<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex;

use CultuurNet\UDB3\Http\LegacyPathRewriter;
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
        $pathHasBeenRewrittenForSilex = false;
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
            function (Request $request, string $path) use ($app, &$pathHasBeenRewrittenForSilex, &$originalRequest) {
                if (!$pathHasBeenRewrittenForSilex) {
                    // If the path has not been rewritten before, rewrite it and dispatch the request again to the Silex
                    // router. Note that the Silex router also requires us to append a trailing slash if it's missing,
                    // whereas the PSR router treats paths with or without trailing slash the same.
                    $rewrittenPath = (new LegacyPathRewriter())->rewritePath($path);
                    $rewrittenPath = preg_replace('/^(.*)(?<!\/)$/', '${1}/', $rewrittenPath);
                    $pathHasBeenRewrittenForSilex = true;
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
                    // If the Silex app still cannot match the new path to a route, this catch-all route will be matched
                    // again and that time we will dispatch it to the new PSR router because
                    // $pathHasBeenRewrittenForSilex will be set to true.
                    return $app->handle($rewrittenRequest, HttpKernelInterface::SUB_REQUEST);
                }

                /** @var Router $psrRouter */
                $psrRouter = $app[Router::class];
                $psrRequest = (new DiactorosFactory())->createRequest($originalRequest);
                $psrRequest = (new LegacyPathRewriter())->rewriteRequest($psrRequest);
                $psrResponse = $psrRouter->handle($psrRequest);
                return (new HttpFoundationFactory())->createResponse($psrResponse);
            }
        )->assert('path', '^.+$');
    }
}
