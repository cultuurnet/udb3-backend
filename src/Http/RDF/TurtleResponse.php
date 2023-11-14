<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\RDF;

use Slim\Psr7\Factory\StreamFactory;
use Slim\Psr7\Headers;
use Slim\Psr7\Interfaces\HeadersInterface;
use Slim\Psr7\Response;

final class TurtleResponse extends Response
{
    public function __construct(string $data = null, $status = 200, ?HeadersInterface $headers = null)
    {
        if (!($headers instanceof HeadersInterface)) {
            $headers = new Headers();
        }

        $headers->setHeader('Content-Type', 'text/turtle');

        parent::__construct(
            $status,
            $headers,
            (new StreamFactory())->createStream($data)
        );
    }
}
