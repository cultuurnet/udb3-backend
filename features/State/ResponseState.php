<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\State;

use CultuurNet\UDB3\Support\Variables;
use Psr\Http\Message\ResponseInterface;

final class ResponseState
{
    private int $statusCode;
    private string $content;
    private array $jsonContent;

    public function setResponse(ResponseInterface $response)
    {
        $this->statusCode = $response->getStatusCode();
        $this->content = $response->getBody()->getContents();
        $this->jsonContent = json_decode($this->content, true);
    }

    public function setResponseAndStoreVariable(
        ResponseInterface $response,
        Variables $variables,
        string $path,
        string $variableName
    ): void {
        $this->setResponse($response);
        $variables->addVariable($variableName, $this->getValueOnPath($path));
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getContent(): string
    {
        return $this->content;
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
