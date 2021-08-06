<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Request;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;
use Slim\Psr7\Factory\StreamFactory;
use Slim\Psr7\Factory\UriFactory;
use Slim\Psr7\Headers;
use Slim\Psr7\Interfaces\HeadersInterface;
use Slim\Psr7\Request;

final class Psr7RequestBuilder
{
    /**
     * @var UriFactory
     */
    private static $uriFactory;

    /**
     * @var StreamFactory
     */
    private static $streamFactory;

    /**
     * @var HeadersInterface|null
     */
    private $headers;

    /**
     * @var UriInterface|null
     */
    private $uri;

    /**
     * @var StreamInterface|null
     */
    private $body;

    public function withUriFromString(string $uri): self
    {
        $c = clone $this;
        $c->uri = self::getUriFactory()->createUri($uri);
        return $c;
    }

    public function withHeader(string $name, string $value): self
    {
        $c = clone $this;
        if (!($c->headers instanceof Headers)) {
            $c->headers = new Headers();
        }
        $c->headers->addHeader($name, $value);
        return $c;
    }

    public function withBodyFromString(string $body): self
    {
        $c = clone $this;
        $c->body = self::getStreamFactory()->createStream($body);
        return $c;
    }

    public function build(string $method): ServerRequestInterface
    {
        return new Request(
            $method,
            $this->uri ?? self::getUriFactory()->createUri(),
            $this->headers ?? new Headers(),
            [],
            [],
            $this->body ?? self::getStreamFactory()->createStream()
        );
    }

    private static function getUriFactory(): UriFactory
    {
        if (!isset(self::$uriFactory)) {
            self::$uriFactory = new UriFactory();
        }
        return self::$uriFactory;
    }

    private static function getStreamFactory(): StreamFactory
    {
        if (!isset(self::$streamFactory)) {
            self::$streamFactory = new StreamFactory();
        }
        return self::$streamFactory;
    }
}
