<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Event;

use CultuurNet\UDB3\Http\Offer\LegacyCalendarRequestBodyParser;
use CultuurNet\UDB3\Http\Offer\LegacyTermsRequestBodyParser;
use CultuurNet\UDB3\Http\Offer\LegacyThemeRequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\CombinedRequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\LegacyNameRequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\RequestBodyParser;
use CultuurNet\UDB3\Iri\IriGeneratorInterface;
use Psr\Http\Message\ServerRequestInterface;
use stdClass;

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
            new LegacyThemeRequestBodyParser(),
            new LegacyCalendarRequestBodyParser()
        );
    }

    public function parse(ServerRequestInterface $request): ServerRequestInterface
    {
        $data = $this->parser->parse($request)->getParsedBody();

        if (is_object($data) && isset($data->location) && !isset($data->location->{'@id'})) {
            if (is_object($data->location) && is_string($data->location->id)) {
                $data->location->{'@id'} = $this->placeIriGenerator->iri($data->location->id);
            }

            // Added to handle the angular UI which passes the location as a string.
            if (is_string($data->location)) {
                $locationId = $data->location;
                $data->location = new stdClass();
                $data->location->{'@id'} = $this->placeIriGenerator->iri($locationId);
            }
        }

        return $request->withParsedBody($data);
    }
}
