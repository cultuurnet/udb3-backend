<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Organizer;

use CultuurNet\UDB3\Http\Request\Body\RequestBodyParser;
use Psr\Http\Message\ServerRequestInterface;
use stdClass;

final class LegacyWebsiteRequestBodyParser implements RequestBodyParser
{
    public function parse(ServerRequestInterface $request): ServerRequestInterface
    {
        $data = $request->getParsedBody();

        if (!($data instanceof stdClass)) {
            return $request;
        }
        $data = clone $data;

        if (isset($data->website) && !isset($data->url)) {
            $data->url = $data->website;
            unset($data->website);
        }

        return $request->withParsedBody($data);
    }
}
