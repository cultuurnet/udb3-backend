<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use CultuurNet\CalendarSummaryV3\CalendarHTMLFormatter;
use CultuurNet\CalendarSummaryV3\CalendarPlainTextFormatter;
use CultuurNet\CalendarSummaryV3\Offer\Offer;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\Request\RouteParameters;
use CultuurNet\UDB3\Http\Response\HtmlResponse;
use CultuurNet\UDB3\Http\Response\PlainTextResponse;
use CultuurNet\UDB3\Offer\ReadModel\JSONLD\OfferJsonDocumentReadRepository;
use CultuurNet\UDB3\ReadModel\DocumentDoesNotExist;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class GetCalendarSummaryRequestHandler implements RequestHandlerInterface
{
    private OfferJsonDocumentReadRepository $documentRepository;

    public function __construct(OfferJsonDocumentReadRepository $documentRepository)
    {
        $this->documentRepository = $documentRepository;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $routeParameters = new RouteParameters($request);
        $offerId = $routeParameters->getOfferId();
        $offerType = $routeParameters->getOfferType();

        try {
            $offerDocument = $this->documentRepository->fetch($offerType, $offerId);
        } catch (DocumentDoesNotExist $e) {
            throw ApiProblem::offerNotFound($offerType, $offerId);
        }

        $offer = Offer::fromJsonLd($offerDocument->getRawBody());

        $parameters = new CalendarSummaryParameters($request);
        $contentType = $parameters->getContentType();
        $language = $parameters->getLanguage();
        $size = $parameters->getSize();
        $hidePastDates = $parameters->shouldHidePastDates();
        $timezone = $parameters->getTimezone();

        switch ($contentType) {
            case CalendarSummaryParameters::HTML:
                $formatter = new CalendarHTMLFormatter($language, $hidePastDates, $timezone);
                $summary = $formatter->format($offer, $size);
                return new HtmlResponse($summary);

            case CalendarSummaryParameters::TEXT:
            default:
                $formatter = new CalendarPlainTextFormatter($language, $hidePastDates, $timezone);
                $summary = $formatter->format($offer, $size);
                return new PlainTextResponse($summary);
        }
    }
}
