<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Request\Body;

use Psr\Http\Message\ServerRequestInterface;
use stdClass;

final class LegacyNameRequestBodyParser implements RequestBodyParser
{
    public function parse(ServerRequestInterface $request): ServerRequestInterface
    {
        $data = $request->getParsedBody();

        if (!($data instanceof stdClass)) {
            return $request;
        }
        $data = clone $data;

        $mainLanguage = $data->mainLanguage ?? null;

        if ($mainLanguage && isset($data->name) && is_string($data->name)) {
            $data->name = (object) [
                $data->mainLanguage => $data->name,
            ];
        }

        return $request->withParsedBody($data);
    }
}
