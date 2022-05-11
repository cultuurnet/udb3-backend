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
    private function getSchemaErrors(array $priceInfos): array
    {
        $errors = [];
        $priceMatrix = [];

        foreach ($priceInfos as $index => $priceInfo) {
            foreach ($priceInfo['name'] as $language => $priceLang) {
                if (isset($priceMatrix[$language]) && in_array($priceLang, $priceMatrix[$language], true)) {
                    $errors[] = new SchemaError(
                        '/priceInfo' . '/' . $index . '/name/' . $language,
                        'Tariff name "' . $priceLang . '" should be unique.'
                    );
                }
                $priceMatrix[$language][] = $priceLang;
            }
        }

        return $errors;
    }
}
