<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Address;

use CultuurNet\UDB3\Address\StreetSuggester\StreetSuggester;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\Request\QueryParameters;
use CultuurNet\UDB3\Http\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class GetStreetRequestHandler implements RequestHandlerInterface
{
    /**
     * @var StreetSuggester[]
     */
    private array $streetSuggesters;

    public function __construct(array $streetSuggesters)
    {
        $this->streetSuggesters = $streetSuggesters;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $queryParameters = new QueryParameters($request);

        $countryCode = $queryParameters->get('country');
        $postalCode = $queryParameters->get('postalCode');
        $locality = $queryParameters->get('locality');
        $query = $queryParameters->get('query');
        $limit = $queryParameters->getAsInt('limit', 5);

        if ($countryCode === null || $postalCode === null || $locality === null || $query === null) {
            throw ApiProblem::queryParameterMissing('country or postalCode or locality or query');
        }

        if (array_key_exists($countryCode, $this->streetSuggesters))
        {
            $content = $this->streetSuggesters[$countryCode]->suggest(
                $postalCode,
                $locality,
                $query,
                $limit
            );
            return new JsonResponse($content);
        }

        throw ApiProblem::queryParameterInvalidValue(
            'country',
            $countryCode,
            [array_keys($this->streetSuggesters)]
        );
    }
}
