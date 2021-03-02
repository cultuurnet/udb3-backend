<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Event;

use CultuurNet\CalendarSummaryV3\CalendarHTMLFormatter;
use CultuurNet\CalendarSummaryV3\CalendarPlainTextFormatter;
use CultuurNet\SearchV3\Serializer\SerializerInterface;
use CultuurNet\SearchV3\ValueObjects\Event;
use CultuurNet\UDB3\Event\ReadModel\DocumentGoneException;
use CultuurNet\UDB3\Http\ApiProblemJsonResponseTrait;
use CultuurNet\UDB3\Http\Management\User\UserIdentificationInterface;
use CultuurNet\UDB3\HttpFoundation\Response\JsonLdResponse;
use CultuurNet\UDB3\ReadModel\DocumentDoesNotExist;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ReadEventRestController
{
    use ApiProblemJsonResponseTrait;
    private const HISTORY_ERROR_FORBIDDEN = 'Forbidden to access event history.';
    private const HISTORY_ERROR_NOT_FOUND = 'An error occurred while getting the history of the event with id %s!';
    private const HISTORY_ERROR_GONE = 'An error occurred while getting the history of the event with id %s which was removed!';
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
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var UserIdentificationInterface
     */
    private $userIdentification;

    public function __construct(
        DocumentRepository $jsonRepository,
        DocumentRepository $historyRepository,
        SerializerInterface $serializer,
        UserIdentificationInterface $userIdentification
    ) {
        $this->jsonRepository = $jsonRepository;
        $this->historyRepository = $historyRepository;
        $this->serializer = $serializer;
        $this->userIdentification = $userIdentification;
    }

    public function get(string $cdbid, Request $request): JsonResponse
    {
        $includeMetadata = (bool) $request->query->get('includeMetadata', false);

        try {
            $event = $this->jsonRepository->fetch($cdbid, $includeMetadata);
        } catch (DocumentDoesNotExist $e) {
            return $this->createApiProblemJsonResponseNotFound(self::GET_ERROR_NOT_FOUND, $cdbid);
        }

        $response = JsonLdResponse::create()
            ->setContent($event->getRawBody());

        $response->headers->set('Vary', 'Origin');

        return $response;
    }

    public function history(string $cdbid): JsonResponse
    {
        $response = null;

        if (!$this->userIdentification->isGodUser()) {
            return $this->createApiProblemJsonResponse(
                self::HISTORY_ERROR_FORBIDDEN,
                $cdbid,
                Response::HTTP_FORBIDDEN
            );
        }

        try {
            $document = $this->historyRepository->get($cdbid);

            if ($document) {
                $history = array_reverse(
                    array_values(
                        json_decode($document->getRawBody(), true) ?? []
                    )
                );

                $response = JsonResponse::create()
                    ->setContent(json_encode($history));

                $response->headers->set('Vary', 'Origin');
            } else {
                $response = $this->createApiProblemJsonResponseNotFound(self::HISTORY_ERROR_NOT_FOUND, $cdbid);
            }
        } catch (DocumentGoneException $documentGoneException) {
            $response = $this->createApiProblemJsonResponseGone(self::HISTORY_ERROR_GONE, $cdbid);
        }

        return $response;
    }

    public function getCalendarSummary(string $cdbid, Request $request): string
    {
        $data = null;
        $response = null;

        $style = $request->query->get('style', 'text');
        $langCode = $request->query->get('langCode', 'nl_BE');
        $hidePastDates = $request->query->get('hidePast', false);
        $timeZone = $request->query->get('timeZone', 'Europe/Brussels');
        $format = $request->query->get('format', 'lg');

        $eventDocument = $this->jsonRepository->fetch($cdbid);
        $event = $this->serializer->deserialize($eventDocument->getRawBody(), Event::class);

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
