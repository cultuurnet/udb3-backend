<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Request\Body;

use Psr\Http\Message\ServerRequestInterface;

trait RequestBodyParserNextTrait
{
    private ?RequestBodyParser $nextParser = null;

    public function next(RequestBodyParser $requestBodyParser): RequestBodyParser
    {
        /** @var $this RequestBodyParser|RequestBodyParserNextTrait $c */
        $c = clone $this;
        $c->nextParser = ($c->nextParser !== null) ? $c->nextParser->next($requestBodyParser) : $requestBodyParser;
        return $c;
    }

    private function callNextParser(ServerRequestInterface $request): ServerRequestInterface
    {
        if ($this->nextParser === null) {
            return $request;
        }
        return $this->nextParser->parse($request);
    }
}
