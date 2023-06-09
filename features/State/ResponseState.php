<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\State;

use CultuurNet\UDB3\Json;
use Psr\Http\Message\ResponseInterface;

final class ResponseState
{
    private int $statusCode;
    private string $content;
    private bool $validJson;
    private array $jsonContent;

    public function setResponse(ResponseInterface $response)
    {
        $this->statusCode = $response->getStatusCode();
        $this->content = $response->getBody()->getContents();

        try {
            $this->jsonContent = Json::decodeAssociatively($this->content);
            $this->validJson = true;
        } catch (\Exception $e) {
            $this->validJson = false;
        }
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function isValidJson(): bool
    {
        return $this->validJson;
    }

    public function getJsonContent(): array
    {
        return $this->jsonContent;
    }

    public function getValueOnPath($path)
    {
        $parts = explode('/', $path);
        $value = $this->jsonContent;

        foreach ($parts as $part) {
            if (isset($value[$part])) {
                $value = $value[$part];
            } else {
                return null;
            }
        }

        return $value;
    }
}
