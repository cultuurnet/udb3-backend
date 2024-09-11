<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Auth;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Adds the necessary headers to a response, after the request has been handled, to make it work with CORS.
 *
 * The headers have to be added for multiple reasons:
 * - Responses to OPTIONS requests require them to tell the browser that the corresponding GET/POST/PUT/PATCH/DELETE
 *   request may be sent.
 * - Responses to basic requests like GET requests without authentication (which do not send a preflight OPTIONS
 *   request) require them to tell the browser that the response data may be shared with the JS code that made the
 *   request.
 *
 * The easiest approach is to just add the headers to every response.
 */
final class CorsHeadersResponseDecorator
{
    public function decorate(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        // Allow any known method regardless of the URL.
        $methods = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'];

        $response = $response
            ->withHeader('Allow', implode(',', $methods))
            ->withHeader('Access-Control-Allow-Methods', implode(',', $methods));

        // Allow the Authorization header to be used.
        // Note that header values must be strings, so we wrap the boolean true in quotes.
        $response = $response->withHeader('Access-Control-Allow-Credentials', 'true');

        // If a specific origin has been requested to be used, echo it back. Otherwise allow *.
        $requestedOrigin = $request->getHeader('Origin');
        $allowedOrigin = count($requestedOrigin) ? $requestedOrigin[0] : '*';
        $response = $response->withHeader('Access-Control-Allow-Origin', $allowedOrigin);

        // If specific headers have been requested to be used, echo them back. Otherwise allow the default headers.
        $requestedHeaders = $request->getHeader('Access-Control-Request-Headers');
        $allowedHeaders = count($requestedHeaders) ? $requestedHeaders[0] : 'authorization,x-api-key';
        return $response->withHeader('Access-Control-Allow-Headers', $allowedHeaders);
    }
}
