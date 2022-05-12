<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use CultuurNet\UDB3\Http\Label\DuplicateLabelValidatingRequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\CombinedRequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\RequestBodyParser;
use Psr\Http\Message\ServerRequestInterface;

final class OfferValidatingRequestBodyParser implements RequestBodyParser
{
    private CombinedRequestBodyParser $combinedRequestBodyParser;

    public function __construct()
    {
        $this->combinedRequestBodyParser = new CombinedRequestBodyParser(
            new BookingInfoValidatingRequestBodyParser(),
            new CalendarValidatingRequestBodyParser(),
            new DuplicateLabelValidatingRequestBodyParser(),
            new PriceInfoValidatingRequestBodyParser()
        );
    }


    public function parse(ServerRequestInterface $request): ServerRequestInterface
    {
        return $this->combinedRequestBodyParser->parse($request);
    }
}
