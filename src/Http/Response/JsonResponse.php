<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Response;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblemException;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use Fig\Http\Message\StatusCodeInterface;
use JsonException;
use Slim\Psr7\Factory\StreamFactory;
use Slim\Psr7\Interfaces\HeadersInterface;
use Slim\Psr7\Response;

class JsonResponse extends Response
{
    public function __construct($data, int $status = StatusCodeInterface::STATUS_OK, ?HeadersInterface $headers = null)
    {
        try {
            $body = (new StreamFactory())->createStream(json_encode($data, JSON_THROW_ON_ERROR));
        } catch (JsonException $e) {
            throw new ApiProblemException(ApiProblem::internalServerError('Could not encode JSON response.'));
        }

        if ($headers instanceof HeadersInterface && !$headers->hasHeader('Content-Type')) {
            $headers->setHeader('Content-Type', 'application/json');
        }

        parent::__construct($status, $headers, $body);
    }
}
