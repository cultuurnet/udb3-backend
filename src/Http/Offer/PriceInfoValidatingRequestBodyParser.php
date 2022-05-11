<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\SchemaError;
use CultuurNet\UDB3\Http\Request\Body\RequestBodyParser;
use CultuurNet\UDB3\Json;
use Psr\Http\Message\ServerRequestInterface;

class PriceInfoValidatingRequestBodyParser implements RequestBodyParser
{
    public function parse(ServerRequestInterface $request): ServerRequestInterface
    {
        $data = $request->getParsedBody();
        if (!isset($data->priceInfo)) {
            return $request;
        }

        $errors = $this->getSchemaErrors(
            Json::decodeAssociatively(Json::encode($data->priceInfo))
        );

        if (count($errors) > 0) {
            throw ApiProblem::bodyInvalidData(...$errors);
        }

        return $request;
    }

    /*
     * @return SchemaError[]
     */
    private function getSchemaErrors(array $priceInfo): array
    {
        $errors = [];
        $priceMatrix = [];

        foreach ($priceInfo as $index => $price) {
            foreach ($price['name'] as $lang => $priceLang) {
                if (isset($priceMatrix[$lang]) && in_array($priceLang, $priceMatrix[$lang], true)) {
                    $errors[] = new SchemaError(
                        '/priceInfo' . '/' . $index . '/name/' . $lang,
                        'Tariff name "' . $priceLang . '" should be unique.'
                    );
                }
                $priceMatrix[$lang][] = $priceLang;
            }
        }

        return $errors;
    }
}
