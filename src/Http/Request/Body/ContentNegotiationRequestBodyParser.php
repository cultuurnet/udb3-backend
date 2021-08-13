<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Request\Body;

use Psr\Http\Message\ServerRequestInterface;

final class ContentNegotiationRequestBodyParser implements RequestBodyParser
{
    private JsonRequestBodyParser $jsonRequestBodyParser;

    public function __construct()
    {
        $this->jsonRequestBodyParser = new JsonRequestBodyParser();
    }

    public function parse(ServerRequestInterface $request)
    {
        $contentType = $this->parseContentType($request);

        switch ($contentType) {
            case 'application/json':
            default:
                return $this->jsonRequestBodyParser->parse($request);
        }
    }

    private function parseContentType(ServerRequestInterface $request): ?string
    {
        $contentTypeList = $request->getHeader('content-type');
        if (!is_array($contentTypeList) || count($contentTypeList) === 0) {
            return null;
        }
        return $contentTypeList[0];
    }
}
