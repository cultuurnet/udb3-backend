<?php

namespace CultuurNet\UDB3\Http;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

interface Psr7FactoryInterface
{
    /**
     * @param string $method
     * @param UriInterface $uri
     * @param array $headers
     * @param string|null $body
     * @param string $protocolVersion
     * @return RequestInterface
     */
    public function createRequest(
        $method,
        UriInterface $uri,
        array $headers = [],
        $body = null,
        $protocolVersion = '1.1'
    );

    /**
     * @param string $uri
     * @return UriInterface
     */
    public function createUri($uri);

    /**
     * @param string $content
     * @return StreamInterface
     */
    public function createContentStream($content);
}
