<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\SchemaError;
use CultuurNet\UDB3\Http\Request\Body\RequestBodyParser;
use CultuurNet\UDB3\Json;
use Psr\Http\Message\ServerRequestInterface;

class PriceInfoDuplicateNameValidatingRequestBodyParser implements RequestBodyParser
{
    public function parse(ServerRequestInterface $request): ServerRequestInterface
    {
        $data = $request->getParsedBody();

        // This is to catch both full Offers and PriceInfo at the root of the request
        if (!isset($data->priceInfo) && !is_array($data)) {
            return $request;
        }

        $priceInfo = $data->priceInfo ?? $data;

        $errors = $this->getSchemaErrors(
            Json::decodeAssociatively(Json::encode($priceInfo))
        );

        if (count($errors) > 0) {
            throw ApiProblem::bodyInvalidData(...$errors);
        }

        return $request;
    }

    /**
     * @return SchemaError[]
     */
    private function getSchemaErrors(array $priceInfos): array
    {
        $errors = [];
        $nameMatrix = [];

        $priceInfos = $this->trimTariffName($priceInfos);

        foreach ($priceInfos as $index => $priceInfo) {
            foreach ($priceInfo['name'] as $language => $name) {
                if (isset($nameMatrix[$language]) && in_array($name, $nameMatrix[$language], true)) {
                    $errors[] = new SchemaError(
                        '/priceInfo' . '/' . $index . '/name/' . $language,
                        'Tariff name "' . $name . '" must be unique.'
                    );
                }
                $nameMatrix[$language][] = $name;
            }
        }

        return $errors;
    }

    private function trimTariffName(array $priceInfos): array
    {
        foreach ($priceInfos as $key => $tariff) {
            if (array_key_exists('name', $tariff)) {
                foreach ($tariff['name'] as $language => $name) {
                    $priceInfos[$key]['name'][$language] = trim($priceInfos[$key]['name'][$language]);
                }
            }
        }
        return $priceInfos;
    }
}
