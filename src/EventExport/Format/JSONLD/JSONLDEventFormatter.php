<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventExport\Format\JSONLD;

use CultuurNet\UDB3\EventExport\CalendarSummary\CalendarSummaryRepositoryInterface;
use CultuurNet\UDB3\EventExport\CalendarSummary\ContentType;
use CultuurNet\UDB3\EventExport\CalendarSummary\Format;
use CultuurNet\UDB3\EventExport\CalendarSummary\SummaryUnavailableException;

class JSONLDEventFormatter
{
    /**
     * @var string[]
     */
    protected ?array $includedProperties;

    /**
     * @var string[]
     */
    protected array $includedTerms;

    private ?CalendarSummaryRepositoryInterface $calendarSummaryRepository;

    /**
     * @param string[]|null $include
     */
    public function __construct(array $include = null, ?CalendarSummaryRepositoryInterface $calendarSummaryRepository = null)
    {
        if ($calendarSummaryRepository) {
            $this->calendarSummaryRepository = $calendarSummaryRepository;
        }

        if ($include) {
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
    }

    private function filterTermsFromProperties($properties): array
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
            $eventObject = json_decode($event);

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

            if (isset($this->calendarSummaryRepository) && in_array('calendarSummary', $includedProperties)) {
                $urlParts = explode('/', $eventObject->{'@id'});
                $eventId = array_pop($urlParts);
                $eventObject->calendarSummary = $this->getCalendarSummary($eventId);
            }

            // filter out base properties
            foreach ($eventObject as $propertyName => $value) {
                if (!in_array($propertyName, $includedProperties)) {
                    unset($eventObject->{$propertyName});
                }
            }

            $event = json_encode($eventObject);
        }

        return $event;
    }

    private function getCalendarSummary(string $eventId): string
    {
        try {
            $calendarSummary = $this->calendarSummaryRepository->get($eventId, ContentType::plain(), Format::sm());
        } catch (SummaryUnavailableException $exception) {
            $calendarSummary = $exception->getMessage();
        }
        return $calendarSummary;
    }
}
