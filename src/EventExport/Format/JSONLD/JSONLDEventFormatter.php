<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventExport\Format\JSONLD;

use CultuurNet\UDB3\EventExport\CalendarSummary\CalendarSummaryRepositoryInterface;
use CultuurNet\UDB3\EventExport\CalendarSummary\ContentType;
use CultuurNet\UDB3\EventExport\CalendarSummary\Format;
use CultuurNet\UDB3\Json;

final class JSONLDEventFormatter
{
    /**
     * @var string[]
     */
    private array $includedProperties;

    /**
     * @var string[]|null
     */
    private ?array $includedTerms = null;

    private CalendarSummaryRepositoryInterface $calendarSummaryRepository;

    /**
     * @param string[] $include
     */
    public function __construct(array $include, CalendarSummaryRepositoryInterface $calendarSummaryRepository)
    {
        $this->calendarSummaryRepository = $calendarSummaryRepository;

        $include[] = '@id';
        // The address property is nested inside location.
        // The whole location property gets included instead of pulling it
        // out and placing it directly on the object.
        if (in_array('address', $include)
                && !in_array('location', $include)
            ) {
            $include[] = 'location';
        }

        // We include bookingInfo if one of its properties is wanted.
        $includedBookingInfoProperties = array_intersect(
            ['bookingInfo.url'],
            $include
        );
        if (!empty($includedBookingInfoProperties)
                && !in_array('bookingInfo', $include)
            ) {
            $include[] = 'bookingInfo';
        }

        if (in_array('attendance', $include)) {
            $include[] = 'attendanceMode';
            $include[] = 'onlineUrl';
        }

        $terms = $this->filterTermsFromProperties($include);
        if (count($terms) > 0) {
            $this->includedTerms = $terms;
            $include[] = 'terms';
        }

        $this->includedProperties = $include;
    }

    private function filterTermsFromProperties(array $properties): array
    {
        $termPrefix = 'terms.';

        $prefixedTerms = array_filter(
            $properties,
            function ($property) use ($termPrefix) {
                return strpos($property, $termPrefix) === 0;
            }
        );
        return array_map(
            function ($term) use ($termPrefix) {
                return str_replace($termPrefix, '', $term);
            },
            $prefixedTerms
        );
    }

    public function formatEvent(string $event): string
    {
        $includedProperties = $this->includedProperties;
        $includedTerms = $this->includedTerms;

        if ($includedProperties) {
            $eventObject = Json::decode($event);

            // filter out terms
            if (property_exists($eventObject, 'terms') && $includedTerms) {
                $filteredTerms = array_filter(
                    $eventObject->terms,
                    function ($term) use ($includedTerms) {
                        return in_array($term->domain, $includedTerms);
                    }
                );

                $eventObject->terms = array_values($filteredTerms);
            }

            if (in_array('calendarSummary', $includedProperties)) {
                $eventId = $this->parseEventIdFromUrl($eventObject);
                $eventObject->calendarSummary = $this->calendarSummaryRepository->get($eventId, ContentType::plain(), Format::md());
            }

            // filter out base properties
            foreach ($eventObject as $propertyName => $value) {
                if (!in_array($propertyName, $includedProperties)) {
                    unset($eventObject->{$propertyName});
                }
            }

            $event = Json::encode($eventObject);
        }

        return $event;
    }

    private function parseEventIdFromUrl(\stdClass $event): string
    {
        $eventUri = $event->{'@id'};
        $uriParts = explode('/', $eventUri);
        return array_pop($uriParts);
    }
}
