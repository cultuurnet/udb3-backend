<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Proxy;

use GuzzleHttp\ClientInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class ProxyRequestHandler implements RequestHandlerInterface
{
    private string $newDomain;
    private ClientInterface $httpClient;
    private string $scheme;
    private ?int $port;

    public function __construct(
        string $newDomain,
        ClientInterface $httpClient,
        string $scheme = 'https',
        ?int $port = null
    ) {
        $this->newDomain = $newDomain;
        $this->httpClient = $httpClient;
        $this->scheme = $scheme;
        $this->port = $port;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        // Make sure to explicitly set the scheme to https and remove the port (if set), because our load balancer on
        // AWS that handles SSL forwards the request to the backend server using the HTTP scheme and port 80, while
        // we want to proxy the request using HTTPS.
        $rewrittenUri = $request->getUri()
            ->withHost($this->newDomain)
            ->withScheme($this->scheme)
            ->withPort($this->port);

        $rewrittenRequest = $request->withUri($rewrittenUri);

        // Disable conversion of HTTP error responses to exceptions so we can return the 4xx and 5xx responses as-is.
        $response = $this->httpClient->send($rewrittenRequest, ['http_errors' => false]);

        // Return the response but remove the Transfer-Encoding header first, as this header is specific to the transfer
        // of a response between two nodes (i.e. this app and SAPI3) and it causes issues when sending the response back
        // to the original client.
        return $response->withoutHeader('Transfer-Encoding');
    }
}
