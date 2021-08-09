<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Event;

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

class ReadEventRestController
{
    private const HISTORY_ERROR_FORBIDDEN = 'Forbidden to access event history.';
    private const HISTORY_ERROR_NOT_FOUND = 'An error occurred while getting the history of the event with id %s!';
    private const GET_ERROR_NOT_FOUND = 'An error occurred while getting the event with id %s!';

    /**
     * @var DocumentRepository
     */
    private $jsonRepository;

    /**
     * @var DocumentRepository
     */
    private $historyRepository;

    /**
     * @var bool
     */
    private $userIsGodUser;

    public function __construct(
        DocumentRepository $jsonRepository,
        DocumentRepository $historyRepository,
        bool $userIsGodUser
    ) {
        $this->jsonRepository = $jsonRepository;
        $this->historyRepository = $historyRepository;
        $this->userIsGodUser = $userIsGodUser;
    }

    public function get(string $cdbid, Request $request): JsonResponse
    {
        $includeMetadata = (bool) $request->query->get('includeMetadata', false);

        $event = $this->fetchEventJson($cdbid, $includeMetadata);

        $response = JsonLdResponse::create()
            ->setContent($event->getRawBody());

        $response->headers->set('Vary', 'Origin');

        return $response;
    }

    public function history(string $cdbid): JsonResponse
    {
        if (!$this->userIsGodUser) {
            throw ApiProblem::custom(
                'about:blank',
                sprintf(self::HISTORY_ERROR_FORBIDDEN),
                403
            )->toException();
        }

        try {
            $document = $this->historyRepository->fetch($cdbid);
            $history = array_reverse(
                array_values(
                    json_decode($document->getRawBody(), true) ?? []
                )
            );

            $response = JsonResponse::create()
                ->setContent(json_encode($history));
            $response->headers->set('Vary', 'Origin');

            return $response;
        } catch (DocumentDoesNotExist $e) {
            throw ApiProblem::custom(
                'about:blank',
                sprintf(self::HISTORY_ERROR_NOT_FOUND, $cdbid),
                404
            )->toException();
        }
    }

    public function getCalendarSummary(string $cdbid, Request $request): string
    {
        $style = $request->query->get('style', 'text');
        $langCode = $request->query->get('langCode', 'nl_BE');
        $hidePastDates = $request->query->getBoolean('hidePast', false);
        $timeZone = $request->query->get('timeZone', 'Europe/Brussels');
        $format = $request->query->get('format', 'lg');

        $eventDocument = $this->fetchEventJson($cdbid);
        $event = Offer::fromJsonLd($eventDocument->getRawBody());

        if ($style !== 'html' && $style !== 'text') {
            throw ApiProblem::custom(
                'about:blank',
                'No style found for ' . $cdbid,
                404
            )->toException();
        }
        if ($style === 'html') {
            $calSum = new CalendarHTMLFormatter($langCode, $hidePastDates, $timeZone);
        } else {
            $calSum = new CalendarPlainTextFormatter($langCode, $hidePastDates, $timeZone);
        }
        $response = $calSum->format($event, $format);

        return $response;
    }

    private function fetchEventJson(string $id, bool $includeMetadata = false): JsonDocument
    {
        try {
            return $this->jsonRepository->fetch($id, $includeMetadata);
        } catch (DocumentDoesNotExist $e) {
            throw ApiProblem::custom(
                'about:blank',
                sprintf(self::GET_ERROR_NOT_FOUND, $id),
                404
            )->toException();
        }
    }
}
