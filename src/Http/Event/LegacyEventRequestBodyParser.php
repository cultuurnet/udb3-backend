<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Event;

use CultuurNet\UDB3\Http\Offer\LegacyCalendarRequestBodyParser;
use CultuurNet\UDB3\Http\Offer\LegacyTermsRequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\CombinedRequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\LegacyNameRequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\RequestBodyParser;
use Psr\Http\Message\ServerRequestInterface;

final class LegacyEventRequestBodyParser implements RequestBodyParser
{
    private CombinedRequestBodyParser $parser;

    public function __construct()
    {
        $this->parser = new CombinedRequestBodyParser(
            new LegacyNameRequestBodyParser(),
            new LegacyTermsRequestBodyParser(),
            new LegacyCalendarRequestBodyParser()
        );
    }

    public function parse(ServerRequestInterface $request): ServerRequestInterface
    {
        return $this->parser->parse($request);
    }
}
