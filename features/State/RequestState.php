<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\State;

final class RequestState
{
    private string $baseUrl = '';
    private string $apiKey = '';
    private string $jwt = '';
    private string $acceptHeader = '';
    private string $contentTypeHeader = '';
    private string $json = '';

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    public function setBaseUrl(string $baseUrl): void
    {
        $this->baseUrl = $baseUrl;
    }

    public function getApiKey(): string
    {
        return $this->apiKey;
    }

    public function setApiKey(string $apiKey): void
    {
        $this->apiKey = $apiKey;
    }

    public function getJwt(): string
    {
        return $this->jwt;
    }

    public function setJwt(string $jwt): void
    {
        $this->jwt = $jwt;
    }

    public function getAcceptHeader(): string
    {
        return $this->acceptHeader;
    }

    public function setAcceptHeader(string $acceptHeader): void
    {
        $this->acceptHeader = $acceptHeader;
    }

    public function getContentTypeHeader(): string
    {
        return $this->contentTypeHeader;
    }

    public function setContentTypeHeader(string $contentTypeHeader): void
    {
        $this->contentTypeHeader = $contentTypeHeader;
    }

    public function getJson(): string
    {
        return $this->json;
    }

    public function setJson(string $json): void
    {
        $this->json = $json;
    }
}