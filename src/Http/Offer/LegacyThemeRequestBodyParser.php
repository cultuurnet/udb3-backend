<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use CultuurNet\UDB3\Http\Request\Body\RequestBodyParser;
use Psr\Http\Message\ServerRequestInterface;
use stdClass;

final class LegacyThemeRequestBodyParser implements RequestBodyParser
{
    public function parse(ServerRequestInterface $request): ServerRequestInterface
    {
        $data = $request->getParsedBody();

        if (!($data instanceof stdClass)) {
            return $request;
        }
        $data = clone $data;

        if (isset($data->theme, $data->theme->id) && $data->theme instanceof stdClass) {
            $terms['id'] = $data->theme->id;

            if (isset($data->theme->label)) {
                $terms['label'] = $data->theme->label;
            }

            if (isset($data->theme->domain)) {
                $terms['domain'] = $data->theme->domain;
            }

            $data->terms[] = (object) $terms;

            unset($data->theme);
        }

        return $request->withParsedBody($data);
    }
}
