<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Request\Body;

use Psr\Http\Message\ServerRequestInterface;

final class CombinedRequestBodyParser implements RequestBodyParser
{
    use RequestBodyParserNextTrait;

    private array $parsers;

    public function __construct(RequestBodyParser ...$requestBodyParsers)
    {
        $this->parsers = $requestBodyParsers;
    }

    public function parse(ServerRequestInterface $request): ServerRequestInterface
    {
        return array_reduce(
            $this->parsers,
            fn (ServerRequestInterface $request, RequestBodyParser $parser) => $parser->parse($request),
            $request
        );
    }
}
