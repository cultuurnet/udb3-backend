<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Response;

use Fig\Http\Message\StatusCodeInterface;
use Slim\Psr7\Factory\StreamFactory;
use Slim\Psr7\Headers;
use Slim\Psr7\Interfaces\HeadersInterface;
use Slim\Psr7\Response;

final class TurtleResponse extends Response
{
    public function __construct($data, int $status = StatusCodeInterface::STATUS_OK, ?HeadersInterface $headers = null)
    {
        $body = (new StreamFactory())->createStream($data);

        $headers = $headers ?? new Headers();
        if (!$headers->hasHeader('Content-Type')) {
            $headers->setHeader('Content-Type', 'text/turtle');
        }

        parent::__construct($status, $headers, $body);
    }
}
