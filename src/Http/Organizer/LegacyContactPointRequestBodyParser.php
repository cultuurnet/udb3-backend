<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Organizer;

use CultuurNet\UDB3\Http\Request\Body\RequestBodyParser;
use Psr\Http\Message\ServerRequestInterface;

class LegacyContactPointRequestBodyParser implements RequestBodyParser
{
    public function parse(ServerRequestInterface $request): ServerRequestInterface
    {
        $data = $request->getParsedBody();

        if (!is_array($data)) {
            return $request;
        }

        $convertedData = [];
        foreach ($data as $contactPoint) {
            if (!isset($convertedData[$contactPoint->type])) {
                $convertedData[$contactPoint->type] = [];
            }

            $convertedData[$contactPoint->type][] = $contactPoint->value;
        }

        return $request->withParsedBody((object)$convertedData);
    }
}
