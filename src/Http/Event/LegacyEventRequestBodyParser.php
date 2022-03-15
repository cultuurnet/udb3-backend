<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Event;

use CultuurNet\UDB3\Http\Offer\LegacyCalendarRequestBodyParser;
use CultuurNet\UDB3\Http\Offer\LegacyTermsRequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\CombinedRequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\LegacyNameRequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\RequestBodyParser;
use CultuurNet\UDB3\Iri\IriGeneratorInterface;
use Psr\Http\Message\ServerRequestInterface;

final class LegacyEventRequestBodyParser implements RequestBodyParser
{
    private IriGeneratorInterface $placeIriGenerator;

    private CombinedRequestBodyParser $parser;

    public function __construct(IriGeneratorInterface $placeIriGenerator)
    {
        $this->placeIriGenerator = $placeIriGenerator;

        $this->parser = new CombinedRequestBodyParser(
            new LegacyNameRequestBodyParser(),
            new LegacyTermsRequestBodyParser(),
            new LegacyCalendarRequestBodyParser()
        );
    }

    public function parse(ServerRequestInterface $request): ServerRequestInterface
    {
        $data = $this->parser->parse($request)->getParsedBody();

        if (is_object($data) && isset($data->location) && !isset($data->location->{'@id'})) {
            $data->location->{'@id'} = $this->placeIriGenerator->iri($data->location->id);
        }

        return $request->withParsedBody($data);
    }
}
