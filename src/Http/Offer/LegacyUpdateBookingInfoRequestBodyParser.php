<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use CultuurNet\UDB3\Http\Request\Body\RequestBodyParser;
use Psr\Http\Message\ServerRequestInterface;
use stdClass;

/**
 * Does a best effort to convert the old UpdateBookingInfo JSON schemas to the new schema.
 */
final class LegacyUpdateBookingInfoRequestBodyParser implements RequestBodyParser
{
    public function parse(ServerRequestInterface $request): ServerRequestInterface
    {
        $data = $request->getParsedBody();

        if ($data instanceof stdClass && isset($data->bookingInfo)) {
            $data = $data->bookingInfo;
        }

        return $request->withParsedBody($data);
    }
}
