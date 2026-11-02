<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http;

use CultuurNet\UDB3\Json;
use JsonException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Factory\StreamFactory;

final class HideAiGeneratedMiddleware implements MiddlewareInterface
{
    private const PROPERTY = 'isAiGenerated';

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($this->hideInRequest($request));
        return $this->hideInResponse($response);
    }

    private function hideInRequest(ServerRequestInterface $request): ServerRequestInterface
    {
        $parsedBody = $request->getParsedBody();
        if (is_array($parsedBody) && array_key_exists(self::PROPERTY, $parsedBody)) {
            unset($parsedBody[self::PROPERTY]);
            $request = $request->withParsedBody($parsedBody);
        }

        $body = $this->removeFromJson((string) $request->getBody());
        if ($body !== null) {
            $request = $request->withBody((new StreamFactory())->createStream($body));
        }

        return $request;
    }

    private function hideInResponse(ResponseInterface $response): ResponseInterface
    {
        $body = $this->removeFromJson((string) $response->getBody());
        if ($body === null) {
            return $response;
        }

        $response = $response->withBody((new StreamFactory())->createStream($body));
        if ($response->hasHeader('Content-Length')) {
            $response = $response->withHeader('Content-Length', (string) strlen($body));
        }
        return $response;
    }

    /**
     * Returns the given JSON without the property, or null if nothing had to be removed.
     */
    private function removeFromJson(string $json): ?string
    {
        if (strpos($json, '"' . self::PROPERTY . '"') === false) {
            return null;
        }

        try {
            $data = Json::decode($json);
        } catch (JsonException $e) {
            return null;
        }

        return Json::encode($this->remove($data));
    }

    private function remove(mixed $data): mixed
    {
        if ($data instanceof \stdClass) {
            unset($data->{self::PROPERTY});
            foreach (get_object_vars($data) as $key => $value) {
                $data->{$key} = $this->remove($value);
            }
            return $data;
        }

        if (is_array($data)) {
            return array_map(fn ($value) => $this->remove($value), $data);
        }

        return $data;
    }
}
