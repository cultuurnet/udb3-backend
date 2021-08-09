<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Place;

use CultuurNet\CalendarSummaryV3\CalendarHTMLFormatter;
use CultuurNet\CalendarSummaryV3\CalendarPlainTextFormatter;
use CultuurNet\CalendarSummaryV3\Offer\Offer;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\HttpFoundation\Response\JsonLdResponse;
use CultuurNet\UDB3\ReadModel\DocumentDoesNotExist;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ReadPlaceRestController
{
    private const GET_ERROR_NOT_FOUND = 'An error occurred while getting the event with id %s!';

    /**
     * @var DocumentRepository
     */
    private $documentRepository;

    public function __construct(
        DocumentRepository $documentRepository
    ) {
        $this->documentRepository = $documentRepository;
    }

    public function get(string $cdbid, Request $request): JsonResponse
    {
        $includeMetadata = (bool) $request->query->get('includeMetadata', false);

        $place = $this->fetchPlaceJson($cdbid, $includeMetadata);

        $response = JsonLdResponse::create()
            ->setContent($place->getRawBody());

        $response->headers->set('Vary', 'Origin');

        return $response;
    }

    public function getCalendarSummary($cdbid, Request $request): Response
    {
        $style = $request->query->get('style', 'text');
        $langCode = $request->query->get('langCode', 'nl_BE');
        $hidePastDates = $request->query->get('hidePast', false);
        $timeZone = $request->query->get('timeZone', 'Europe/Brussels');
        $format = $request->query->get('format', 'lg');

        $data = $this->fetchPlaceJson($cdbid);
        $place = Offer::fromJsonLd($data->getRawBody());

        if ($style !== 'html' && $style !== 'text') {
            throw ApiProblem::custom(
                'about:blank',
                'No style found for ' . $style,
                404
            );
        }

        if ($style === 'html') {
            $calSum = new CalendarHTMLFormatter($langCode, $hidePastDates, $timeZone);
        } else {
            $calSum = new CalendarPlainTextFormatter($langCode, $hidePastDates, $timeZone);
        }

        return new Response($calSum->format($place, $format));
    }

    private function fetchPlaceJson(string $id, bool $includeMetadata = false): JsonDocument
    {
        try {
            return $this->documentRepository->fetch($id, $includeMetadata);
        } catch (DocumentDoesNotExist $e) {
            throw ApiProblem::custom(
                'about:blank',
                sprintf(self::GET_ERROR_NOT_FOUND, $id),
                404
            );
        }
    }
}
