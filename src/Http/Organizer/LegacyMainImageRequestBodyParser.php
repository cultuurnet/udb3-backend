<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Organizer;

use CultuurNet\UDB3\Http\Request\Body\RequestBodyParser;
use Psr\Http\Message\ServerRequestInterface;

class LegacyMainImageRequestBodyParser implements RequestBodyParser
{
    public function parse(ServerRequestInterface $request): ServerRequestInterface
    {
        $data = $request->getParsedBody();

        if (isset($data->imageId)) {
            return $request;
        }

        if (!isset($data->mediaObjectId)) {
            return $request;
        }

        $convertedData = [];
        $convertedData['imageId'] = $data->mediaObjectId;

        return $request->withParsedBody((object)$convertedData);
    }
}
