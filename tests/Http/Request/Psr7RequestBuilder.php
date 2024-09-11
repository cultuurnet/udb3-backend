<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Request;

use CultuurNet\UDB3\Json;
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
    private static UriFactory $uriFactory;

    private static StreamFactory $streamFactory;

    private ?HeadersInterface $headers = null;

    private ?UriInterface $uri = null;

    private ?StreamInterface $body = null;

    private array $files = [];

    private array $routeParameters = [];

    /**
     * @var array|object|null
     */
    private $parsedBody = null;

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

    public function withJsonBodyFromArray(array $body): self
    {
        $c = clone $this;
        $c->body = self::getStreamFactory()->createStream(Json::encode($body));
        return $c;
    }

    public function withJsonBodyFromObject(object $body): self
    {
        $c = clone $this;
        $c->body = self::getStreamFactory()->createStream(Json::encode($body));
        return $c;
    }

    /**
     * @param array|object $parsedBody
     */
    public function withParsedBody($parsedBody): self
    {
        $c = clone $this;
        $c->parsedBody = $parsedBody;
        return $c;
    }

    public function withFiles(array $files): self
    {
        $c = clone $this;
        $c->files = $files;
        return $c;
    }

    public function withRouteParameter(string $parameterName, string $parameterValue): self
    {
        $c = clone $this;
        $c->routeParameters[$parameterName] = $parameterValue;
        return $c;
    }

    public function build(string $method): ServerRequestInterface
    {
        $request = new Request(
            $method,
            $this->uri ?? self::getUriFactory()->createUri(),
            $this->headers ?? new Headers(),
            [],
            [],
            $this->body ?? self::getStreamFactory()->createStream(),
            $this->files,
        );

        foreach ($this->routeParameters as $routeParameter => $value) {
            $request = $request->withAttribute($routeParameter, $value);
        }

        return $request->withParsedBody($this->parsedBody);
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
