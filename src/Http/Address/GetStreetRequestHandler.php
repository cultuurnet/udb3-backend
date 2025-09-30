<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Address;

use CultuurNet\UDB3\Address\StreetSuggester\StreetSuggester;
use CultuurNet\UDB3\Http\Request\QueryParameters;
use CultuurNet\UDB3\Http\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class GetStreetRequestHandler implements RequestHandlerInterface
{
    private StreetSuggester $streetSuggester;
    public function __construct(StreetSuggester $streetSuggester)
    {
        $this->streetSuggester = $streetSuggester;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $queryParameters = new QueryParameters($request);

        $content = $this->streetSuggester->suggest(
            $queryParameters->get('postalCode'),
            $queryParameters->get('locality'),
            $queryParameters->get('query')
        );

        return new JsonResponse($content);
    }
}
