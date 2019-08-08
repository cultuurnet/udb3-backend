<?php

namespace CultuurNet\UDB3\Http\Event;

use CultuurNet\CalendarSummaryV3\CalendarHTMLFormatter;
use CultuurNet\CalendarSummaryV3\CalendarPlainTextFormatter;
use CultuurNet\SearchV3\Serializer\SerializerInterface;
use CultuurNet\SearchV3\ValueObjects\Event;
use CultuurNet\UDB3\Http\ApiProblemJsonResponseTrait;
use Symfony\Component\HttpFoundation\JsonResponse;
use CultuurNet\UDB3\Event\ReadModel\DocumentGoneException;
use CultuurNet\UDB3\Event\ReadModel\DocumentRepositoryInterface;
use CultuurNet\UDB3\Event\EventServiceInterface;
use CultuurNet\UDB3\Http\JsonLdResponse;
use Symfony\Component\HttpFoundation\Request;

class ReadEventRestController
{
    const HISTORY_ERROR_NOT_FOUND = 'An error occurred while getting the history of the event with id %s!';
    const HISTORY_ERROR_GONE = 'An error occurred while getting the history of the event with id %s which was removed!';
    const GET_ERROR_NOT_FOUND = 'An error occurred while getting the event with id %s!';
    const GET_ERROR_GONE = 'An error occurred while getting the event with id %s which was removed!';

    use ApiProblemJsonResponseTrait;

    /**
     * @var EventServiceInterface
     */
    private $service;

    /**
     * @var DocumentRepositoryInterface
     */
    private $historyRepository;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @param EventServiceInterface $service
     * @param DocumentRepositoryInterface $historyRepository
     * @param SerializerInterface $serializer
     */
    public function __construct(
        EventServiceInterface $service,
        DocumentRepositoryInterface $historyRepository,
        SerializerInterface $serializer
    ) {
        $this->service = $service;
        $this->historyRepository = $historyRepository;
        $this->serializer = $serializer;
    }

    /**
     * @param string $cdbid
     * @return JsonLdResponse
     */
    public function get($cdbid)
    {
        $response = null;

        $event = $this->service->getEvent($cdbid);

        if ($event) {
            $response = JsonLdResponse::create()
                ->setContent($event);

            $response->headers->set('Vary', 'Origin');
        } else {
            $response = $this->createApiProblemJsonResponseNotFound(self::GET_ERROR_NOT_FOUND, $cdbid);
        }

        return $response;
    }

    /**
     * @param string $cdbid
     * @return JsonResponse
     */
    public function history($cdbid)
    {
        $response = null;

        try {
            $document = $this->historyRepository->get($cdbid);

            if ($document) {
                $response = JsonResponse::create()
                    ->setContent($document->getRawBody());

                $response->headers->set('Vary', 'Origin');
            } else {
                $response = $this->createApiProblemJsonResponseNotFound(self::HISTORY_ERROR_NOT_FOUND, $cdbid);
            }
        } catch (DocumentGoneException $documentGoneException) {
            $response = $this->createApiProblemJsonResponseGone(self::HISTORY_ERROR_GONE, $cdbid);
        }

        return $response;
    }

    /**
     * @param string $cdbid
     *
     * @return string
     */
    public function getCalendarSummary($cdbid, Request $request)
    {
        $data = null;
        $response = null;

        $style = $request->query->get('style', 'text');
        $langCode = $request->query->get('langCode', 'nl_BE');
        $hidePastDates = $request->query->get('hidePast', false);
        $timeZone = $request->query->get('timeZone', 'Europe/Brussels');
        $format = $request->query->get('format', 'lg');

        $data = $this->service->getEvent($cdbid);
        $event = $this->serializer->deserialize($data, Event::class);

        if ($style !== 'html' && $style !== 'text') {
            $response = $this->createApiProblemJsonResponseNotFound('No style found for ' . $style, $cdbid);
        } else {
            if ($style === 'html') {
                $calSum = new CalendarHTMLFormatter($langCode, $hidePastDates, $timeZone);
            } else {
                $calSum = new CalendarPlainTextFormatter($langCode, $hidePastDates, $timeZone);
            }
            $response = $calSum->format($event, $format);
        }


        return $response;
    }
}
