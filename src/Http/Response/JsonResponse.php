<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Response;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use Fig\Http\Message\StatusCodeInterface;
use JsonException;
use Slim\Psr7\Factory\StreamFactory;
use Slim\Psr7\Headers;
use Slim\Psr7\Interfaces\HeadersInterface;
use Slim\Psr7\Response;

class JsonResponse extends Response
{
    /**
     * @param null|string|array|object $data
     * @throws ApiProblem
     */
    public function __construct($data, int $status = StatusCodeInterface::STATUS_OK, ?HeadersInterface $headers = null)
    {
        try {
            if (!is_string($data)) {
                $data = json_encode($data, JSON_THROW_ON_ERROR);
            }

            $body = (new StreamFactory())->createStream($data);
        } catch (JsonException $e) {
            throw ApiProblem::internalServerError('Could not encode JSON response.');
        }

        $headers = $headers ?? new Headers();
        if (!$headers->hasHeader('Content-Type')) {
            $headers->setHeader('Content-Type', 'application/json');
        }

        parent::__construct($status, $headers, $body);
    }
}
