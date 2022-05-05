<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\SchemaError;
use CultuurNet\UDB3\Http\Request\Body\RequestBodyParser;
use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\Model\Serializer\ValueObject\Price\PriceInfoDenormalizer;
use CultuurNet\UDB3\Model\ValueObject\Price\PriceInfo;
use Psr\Http\Message\ServerRequestInterface;

class PriceInfoValidatingRequestBodyParser implements RequestBodyParser
{
    public function parse(ServerRequestInterface $request): ServerRequestInterface
    {
        $data = $request->getParsedBody();
        if (!isset($data->priceInfo) || !is_array($data->priceInfo)) {
            return $request;
        }

        $priceInfoDenormalizer = new PriceInfoDenormalizer();
        $priceInfo = $priceInfoDenormalizer->denormalize(
            Json::decode(Json::encode($data->priceInfo)),
            PriceInfo::class
        );

        if ($priceInfo->getTariffs()->hasDuplicates()) {
            throw ApiProblem::bodyInvalidData(
                new SchemaError(
                    '/priceInfo',
                    'Tariff names should be unique.'
                )
            );
        }

        return $request;
    }
}
