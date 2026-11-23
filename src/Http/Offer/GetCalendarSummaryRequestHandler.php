<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\Request\RouteParameters;
use CultuurNet\UDB3\Http\Response\HtmlResponse;
use CultuurNet\UDB3\Http\Response\PlainTextResponse;
use CultuurNet\UDB3\Search\Sapi3CalendarSummaryService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class GetCalendarSummaryRequestHandler implements RequestHandlerInterface
{
    private Sapi3CalendarSummaryService $calendarSummaryService;

    public function __construct(Sapi3CalendarSummaryService $calendarSummaryService)
    {
        $this->calendarSummaryService = $calendarSummaryService;
    }

    /**
     * @throws ApiProblem
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $routeParameters = new RouteParameters($request);
        $parameters = new CalendarSummaryParameters($request);

        $summary = $this->calendarSummaryService->get(
            $routeParameters->getOfferType(),
            $routeParameters->getOfferId(),
            $parameters->toSearchApiQueryParameters()
        );

        if ($parameters->getContentType() === CalendarSummaryParameters::HTML) {
            return new HtmlResponse($summary);
        }
        return new PlainTextResponse($summary);
    }
}
