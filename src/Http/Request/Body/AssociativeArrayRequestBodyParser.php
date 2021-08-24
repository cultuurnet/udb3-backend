<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Request\Body;

use Psr\Http\Message\ServerRequestInterface;

/**
 * Converts any objects inside the parsed body on the request to associative arrays.
 */
final class AssociativeArrayRequestBodyParser implements RequestBodyParser
{
    public function parse(ServerRequestInterface $request): ServerRequestInterface
    {
        return $request->withParsedBody(
            $this->convertToAssociativeArray(
                $request->getParsedBody()
            )
        );
    }

    /**
     * Converts any objects inside the data to associative arrays, including $data itself.
     *
     * If $data is a string, integer, boolean, null etc it will just be returned.
     * If $data is an array, the function will loop over all entries and call itself on the values.
     * If $data is an object, it will be cast to an array first and then the function will handle it like arrays.
     *
     */
    private function convertToAssociativeArray($data)
    {
        if (!is_array($data) && !is_object($data)) {
            return $data;
        }
        $data = (array) $data;
        foreach ($data as $key => $value) {
            $data[$key] = $this->convertToAssociativeArray($value);
        }
        return $data;
    }
}
