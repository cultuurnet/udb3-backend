<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use CultuurNet\UDB3\Http\Label\DuplicateLabelValidatingRequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\CombinedRequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\MainLanguageValidatingRequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\RequestBodyParser;
use CultuurNet\UDB3\Offer\OfferType;
use Psr\Http\Message\ServerRequestInterface;

final class OfferValidatingRequestBodyParser implements RequestBodyParser
{
    private CombinedRequestBodyParser $combinedRequestBodyParser;

    public function __construct(OfferType $offerType)
    {
        if ($offerType->sameAs(OfferType::event())) {
            $this->combinedRequestBodyParser = new CombinedRequestBodyParser(
                new BookingInfoValidatingRequestBodyParser(),
                new CalendarValidatingRequestBodyParser(),
                new DuplicateLabelValidatingRequestBodyParser(),
                new PriceInfoValidatingRequestBodyParser(),
                MainLanguageValidatingRequestBodyParser::createForEvent()
            );
        } else {
            $this->combinedRequestBodyParser = new CombinedRequestBodyParser(
                new BookingInfoValidatingRequestBodyParser(),
                new CalendarValidatingRequestBodyParser(),
                new DuplicateLabelValidatingRequestBodyParser(),
                new PriceInfoValidatingRequestBodyParser(),
                MainLanguageValidatingRequestBodyParser::createForPlace()
            );
        }
    }

    public function parse(ServerRequestInterface $request): ServerRequestInterface
    {
        return $this->combinedRequestBodyParser->parse($request);
    }
}
