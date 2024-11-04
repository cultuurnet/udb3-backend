<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Request\Body;

use Psr\Http\Message\ServerRequestInterface;
use stdClass;

/**
 * Recursively removes null properties from objects, and null values from arrays.
 */
final class RemoveNullPropertiesRequestBodyParser implements RequestBodyParser
{
    public function parse(ServerRequestInterface $request): ServerRequestInterface
    {
        $json = $request->getParsedBody();

        // Even though we do not decode associatively, the JSON can still be an array because some endpoints expect a
        // JSON array as body. (Not associative but an actual list.)
        if ($json instanceof stdClass) {
            $json = $this->removeNullPropertiesFromObject($json);
        }
        if (is_array($json)) {
            $json = $this->removeNullValuesFromArray($json);
        }

        return $request->withParsedBody($json);
    }

    private function removeNullPropertiesFromObject(stdClass $data): stdClass
    {
        $newData = new stdClass();
        foreach ((array) $data as $property => $value) {
            if ($value instanceof stdClass) {
                $value = $this->removeNullPropertiesFromObject($value);
            }
            if (is_array($value)) {
                $value = $this->removeNullValuesFromArray($value);
            }
            if ($value !== null) {
                $newData->$property = $value;
            }
        }
        return $newData;
    }

    private function removeNullValuesFromArray(array $data): array
    {
        $data = array_filter($data, fn ($value) => $value !== null);
        return array_map(
            function ($value) {
                if ($value instanceof stdClass) {
                    return $this->removeNullPropertiesFromObject($value);
                }
                if (is_array($value)) {
                    return $this->removeNullValuesFromArray($value);
                }
                return $value;
            },
            $data
        );
    }
}
