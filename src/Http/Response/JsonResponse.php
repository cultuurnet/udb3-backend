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
use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;
use Symfony\Component\HttpFoundation\Response as HttpFoundationResponse;

class JsonResponse extends Response
{
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

    /**
     * @deprecated
     *   Only use for backward compatibility with Symfony's HTTP foundation where we cannot use PSR7 yet.
     *   For example Silex's middlewares, Symfony's security component, etc.
     *   Controllers / request handlers can just be refactored to return PSR7 responses.
     */
    public function toHttpFoundationResponse(): HttpFoundationResponse
    {
        return (new HttpFoundationFactory())
            ->createResponse($this);
    }
}
