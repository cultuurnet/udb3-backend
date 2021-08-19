<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Request\Body;

final class RequestBodyParserFactory
{
    /**
     * Returns a "base" RequestBodyParser to use in every request handler, combined with optionally extra parsers.
     *
     * @param RequestBodyParser ...$nextParsers
     *  Parser(s) to append to the base parser. (Optional)
     *
     * @return RequestBodyParser
     */
    public static function createBaseParser(RequestBodyParser ...$nextParsers): RequestBodyParser
    {
        return new CombinedRequestBodyParser(
            new JsonRequestBodyParser(),
            ...$nextParsers
        );
    }
}
