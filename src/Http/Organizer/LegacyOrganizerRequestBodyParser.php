<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Organizer;

use CultuurNet\UDB3\Http\Request\Body\CombinedRequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\LegacyAddressRequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\LegacyNameRequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\RequestBodyParser;
use Psr\Http\Message\ServerRequestInterface;
use stdClass;

final class LegacyOrganizerRequestBodyParser implements RequestBodyParser
{
    private CombinedRequestBodyParser $parser;

    public function __construct()
    {
        $this->parser = new CombinedRequestBodyParser(
            new LegacyAddressRequestBodyParser(),
            new LegacyNameRequestBodyParser(),
            new LegacyWebsiteRequestBodyParser()
        );
    }

    public function parse(ServerRequestInterface $request): ServerRequestInterface
    {
        $parsedRequest = $this->parser->parse($request);

        $data = $parsedRequest->getParsedBody();

        if (!($data instanceof stdClass)) {
            return $request;
        }
        $data = clone $data;

        $convertedContactPoint = [];
        if (isset($data->contact) && is_array($data->contact)) {
            foreach ($data->contact as $contactPoint) {
                if (!isset($convertedContactPoint[$contactPoint->type])) {
                    $convertedContactPoint[$contactPoint->type] = [];
                }

                $convertedContactPoint[$contactPoint->type][] = $contactPoint->value;
            }
        }

        if (count($convertedContactPoint) > 0) {
            $data->contactPoint = (object) $convertedContactPoint;
        }

        return $request->withParsedBody($data);
    }
}
