<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use _HumbugBox113887eee2b6\___PHPSTORM_HELPERS\this;
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

       $priceInfos = $this->trimArrayValues($priceInfos);

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

    private function trimArrayValues(array $array): array
    {
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $array[$key] = $this->trimArrayValues($value);
            } else {
                $array[$key] = is_string($value) ? trim($value) : $value;
            }
        }
        return $array;
    }
}
