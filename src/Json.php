<?php

declare(strict_types=1);

namespace CultuurNet\UDB3;

use _HumbugBox113887eee2b6\___PHPSTORM_HELPERS\object;
use JsonException;

final class Json
{
    public static int $depth = 512;

    /**
     * @param mixed $value
     *   Data to encode as JSON, usually an array or stdClass object
     *
     * @return string
     *   Encoded JSON.
     *
     * @throws JsonException
     *   If the JSON could not be encoded, for example because of too much nesting.
     */
    public static function encode($value): string
    {
        return json_encode($value, JSON_THROW_ON_ERROR, self::$depth);
    }

    /**
     * @throws JsonException
     *   If the JSON could not be decoded, for example because the syntax is invalid.
     */
    public static function decode(string $data) : object
    {
        return json_decode($data, false, self::$depth, JSON_THROW_ON_ERROR);
    }

    /**
     * @throws JsonException
     *   If the JSON could not be decoded, for example because the syntax is invalid.
     */
    public static function decodeAssociatively(string $data) : array
    {
        return json_decode($data, true, self::$depth, JSON_THROW_ON_ERROR);
    }
}
