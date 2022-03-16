<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use CultuurNet\UDB3\Http\Request\Body\RequestBodyParser;
use Psr\Http\Message\ServerRequestInterface;
use stdClass;

final class LegacyTermsRequestBodyParser implements RequestBodyParser
{
    public function parse(ServerRequestInterface $request): ServerRequestInterface
    {
        $data = $request->getParsedBody();

        if (!($data instanceof stdClass)) {
            return $request;
        }
        $data = clone $data;

        if (isset($data->type, $data->type->id) && $data->type instanceof stdClass) {
            $terms['id'] = $data->type->id;

            if (isset($data->type->label)) {
                $terms['label'] = $data->type->label;
            }

            if (isset($data->type->domain)) {
                $terms['domain'] = $data->type->domain;
            }

            $data->terms[] = (object) $terms;

            unset($data->type);
        }

        return $request->withParsedBody($data);
    }
}
