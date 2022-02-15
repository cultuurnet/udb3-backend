<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Place;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\SchemaError;
use CultuurNet\UDB3\Http\Request\Body\RequestBodyParser;
use Psr\Http\Message\ServerRequestInterface;

final class PriceInfoValidationRequestBodyParser implements RequestBodyParser
{
    public function parse(ServerRequestInterface $request): ServerRequestInterface
    {
        $data = $request->getParsedBody();

        if (isset($data->priceInfo)) {
            $baseTariffs = array_filter(
                $data->priceInfo,
                fn ($tariff) => $tariff->category === 'base'
            );

            if (count($baseTariffs) !== 1) {
                throw ApiProblem::bodyInvalidData(
                    new SchemaError('/priceInfo', 'Exactly one base tariff expected')
                );
            }
        }

        return $request;
    }
}
