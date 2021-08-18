<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Request\Body;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use Psr\Http\Message\ServerRequestInterface;

interface RequestBodyParser
{
    /**
     * Parses and validates a request's content.
     *
     * @param ServerRequestInterface $request
     *   The incoming (PSR-7) request of which the body has to be parsed.
     *   Some implementations may require that the given $request already has a getParsedBody() that does not return
     *   null, e.g. if they only do validation or transformations after the actual parsing.
     *
     * @return ServerRequestInterface
     *   New ServerRequestInterface instance that has a getParsedBody() that does not return null.
     *
     * @throws ApiProblem
     */
    public function parse(ServerRequestInterface $request): ServerRequestInterface;

    /**
     * Registers the RequestBodyParser to use after the current one.
     * Implementations MUST call parse() on this next parser inside their own parse() method after executing their own
     * logic, unless they throw an ApiProblem exception.
     *
     * If a next parser is already set, the implementation MUST call next() on the previously set next parser to append
     * the new parser to the chain.
     *
     * @see RequestBodyParserNextTrait
     *   For easy standard implementation.
     *
     * @param RequestBodyParser $requestBodyParser
     *   The RequestBodyParser to call parse() on next.
     *
     * @return RequestBodyParser
     *   The parser that the next() method was originally called on, for easy method chaining.
     *   For example $parser->next($inBetweenParser)->next($finalParser)->parse($request).
     */
    public function next(RequestBodyParser $requestBodyParser): RequestBodyParser;
}
