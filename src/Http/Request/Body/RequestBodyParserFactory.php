<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Request\Body;

final class RequestBodyParserFactory
{
    /**
     * Returns a "base" RequestBodyParser to use in every request handler, which can be extended with other parsers
     * using next().
     *
     * @return RequestBodyParser
     *  Currently just a JsonRequestBodyParser, but can be expanded later to for example add a parser that transforms
     *  data from a new format to JSON first for easier further internal handling based on the Content-Type in the
     *  request.
     */
    public static function createBaseParser(): RequestBodyParser
    {
        return new JsonRequestBodyParser();
    }
}
