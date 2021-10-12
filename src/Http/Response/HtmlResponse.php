<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Response;

use Fig\Http\Message\StatusCodeInterface;
use Slim\Psr7\Factory\StreamFactory;
use Slim\Psr7\Headers;
use Slim\Psr7\Interfaces\HeadersInterface;
use Slim\Psr7\Response;

final class HtmlResponse extends Response
{
    public function __construct(
        string $content,
        int $status = StatusCodeInterface::STATUS_OK,
        ?HeadersInterface $headers = null
    ) {
        $contentStream = (new StreamFactory())->createStream($content);

        if (!($headers instanceof HeadersInterface)) {
            $headers = new Headers();
        }

        $headers->setHeader('Content-Type', 'text/html; charset=UTF-8');

        parent::__construct($status, $headers, $contentStream);
    }
}
