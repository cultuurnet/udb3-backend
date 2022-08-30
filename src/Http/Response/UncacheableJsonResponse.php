<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Response;

use Fig\Http\Message\StatusCodeInterface;
use Slim\Psr7\Headers;
use Slim\Psr7\Interfaces\HeadersInterface;

final class UncacheableJsonResponse extends JsonResponse
{
    public function __construct($data, int $status = StatusCodeInterface::STATUS_OK, ?HeadersInterface $headers = null)
    {
        $headers = $headers ?? new Headers();
        parent::__construct($data, $status, $headers->setHeader('Cache-Control', 'private'));
    }
}
