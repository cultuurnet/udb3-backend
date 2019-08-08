<?php

namespace CultuurNet\UDB3\Http\Place;

use CultuurNet\CalendarSummaryV3\CalendarHTMLFormatter;
use CultuurNet\CalendarSummaryV3\CalendarPlainTextFormatter;
use CultuurNet\SearchV3\Serializer\SerializerInterface;
use CultuurNet\SearchV3\ValueObjects\Place;
use CultuurNet\UDB3\EntityServiceInterface;
use CultuurNet\UDB3\Http\ApiProblemJsonResponseTrait;
use CultuurNet\UDB3\Http\JsonLdResponse;
use Symfony\Component\HttpFoundation\Request;

class ReadPlaceRestController
{
    const GET_ERROR_NOT_FOUND = 'An error occurred while getting the event with id %s!';
    const GET_ERROR_GONE = 'An error occurred while getting the event with id %s which was removed!';

    use ApiProblemJsonResponseTrait;

    /**
     * @var EntityServiceInterface
     */
    private $service;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @param EntityServiceInterface $service
     * @param SerializerInterface $serializer
     */
    public function __construct(
        EntityServiceInterface $service,
        SerializerInterface $serializer
    ) {
        $this->service = $service;
        $this->serializer = $serializer;
    }

    /**
     * @param $cdbid
     * @return JsonLdResponse
     */
    public function get($cdbid)
    {
        $response = null;

        $place = $this->service->getEntity($cdbid);

        if ($place) {
            $response = JsonLdResponse::create()
                ->setContent($place);

            $response->headers->set('Vary', 'Origin');
        } else {
            $response = $this->createApiProblemJsonResponseNotFound(self::GET_ERROR_NOT_FOUND, $cdbid);
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

        $data = $this->service->getEntity($cdbid);
        $place = $this->serializer->deserialize($data, Place::class);

        if ($style !== 'html' && $style !== 'text') {
            $response = $this->createApiProblemJsonResponseNotFound('No style found for ' . $style, $cdbid);
        } else {
            if ($style === 'html') {
                $calSum = new CalendarHTMLFormatter($langCode, $hidePastDates, $timeZone);
            } else {
                $calSum = new CalendarPlainTextFormatter($langCode, $hidePastDates, $timeZone);
            }
            $response = $calSum->format($place, $format);
        }

        return $response;
    }
}
