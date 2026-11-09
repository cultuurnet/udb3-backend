<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Media;

use CultuurNet\UDB3\Json;
use JsonException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Factory\StreamFactory;

/**
 * Removes every isAiGenerated property from the request body, so images can not be marked as AI generated
 * while the enable_ai_generated_images feature flag is disabled.
 */
final class IgnoreIsAiGeneratedMiddleware implements MiddlewareInterface
{
    private const PROPERTY = 'isAiGenerated';

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $parsedBody = $request->getParsedBody();
        if (is_array($parsedBody) && array_key_exists(self::PROPERTY, $parsedBody)) {
            unset($parsedBody[self::PROPERTY]);
            $request = $request->withParsedBody($parsedBody);
        }

        $body = (string) $request->getBody();
        if (!str_contains($body, '"' . self::PROPERTY . '"')) {
            return $handler->handle($request);
        }

        try {
            $decoded = Json::decode($body);
        } catch (JsonException $e) {
            // Invalid JSON is reported by the request handler itself.
            return $handler->handle($request);
        }

        $body = (new StreamFactory())->createStream(Json::encode($this->removeProperty($decoded)));

        return $handler->handle($request->withBody($body));
    }

    private function removeProperty(mixed $data): mixed
    {
        if (is_object($data)) {
            unset($data->{self::PROPERTY});
            foreach (get_object_vars($data) as $key => $value) {
                $data->{$key} = $this->removeProperty($value);
            }
        }

        if (is_array($data)) {
            $data = array_map(fn ($value) => $this->removeProperty($value), $data);
        }

        return $data;
    }
}
