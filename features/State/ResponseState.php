<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\State;

use CultuurNet\UDB3\Json;
use Psr\Http\Message\ResponseInterface;

final class ResponseState
{
    private int $statusCode;
    private string $content;
    private array $headers;
    private bool $validJson;
    private array $jsonContent;

    public function setResponse(ResponseInterface $response): void
    {
        $this->statusCode = $response->getStatusCode();
        $this->content = $response->getBody()->getContents();
        $this->headers = $response->getHeaders();

        try {
            $this->jsonContent = Json::decodeAssociatively($this->content);
            $this->validJson = true;
        } catch (\Throwable $e) {
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

    public function getContentType(): string
    {
        return $this->headers['Content-Type'][0] ?? '';
    }

    public function isValidJson(): bool
    {
        return $this->validJson;
    }

    public function getJsonContent(): array
    {
        return $this->jsonContent;
    }

    public function getTotalItems(): int
    {
        return $this->jsonContent['totalItems'] ?? 0;
    }

    /**
     * @return mixed|null
     */
    public function getValueOnPath(string $path)
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
