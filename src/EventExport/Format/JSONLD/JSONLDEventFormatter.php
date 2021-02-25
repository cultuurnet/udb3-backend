<?php

namespace CultuurNet\UDB3\EventExport\Format\JSONLD;

class JSONLDEventFormatter
{
    /**
     * @var string[]
     */
    protected $includedProperties;

    /**
     * @var string[]
     */
    protected $includedTerms;

    /**
     * @param string[]|null $include A list of properties to include when
     * formatting the events.
     */
    public function __construct($include = null)
    {
        if ($include) {
            $include[] = '@id';
            // The address property is nested inside location.
            // The whole location property gets included instead of pulling it
            // out and placing it directly on the object.
            if (in_array('address', $include)
                && !in_array('location', $include)
            ) {
                array_push($include, 'location');
            }

            // We include bookingInfo if one of its properties is wanted.
            $includedBookingInfoProperties = array_intersect(
                ['bookingInfo.url'],
                $include
            );
            if (!empty($includedBookingInfoProperties)
                && !in_array('bookingInfo', $include)
            ) {
                array_push($include, 'bookingInfo');
            }

            $terms = $this->filterTermsFromProperties($include);
            if (count($terms) > 0) {
                $this->includedTerms = $terms;
                $include[] = 'terms';
            }

            $this->includedProperties = $include;
        }
    }

    private function filterTermsFromProperties($properties)
    {
        $termPrefix = 'terms.';

        $prefixedTerms = array_filter(
            $properties,
            function ($property) use ($termPrefix) {
                return strpos($property, $termPrefix) === 0;
            }
        );
        $terms = array_map(
            function ($term) use ($termPrefix) {
                return str_replace($termPrefix, "", $term);
            },
            $prefixedTerms
        );

        return $terms;
    }

    /**
     * @param   string $event A string representing an event in json-ld format
     * @return  string  The event string formatted with all the included
     *                  properties and terms
     */
    public function formatEvent($event)
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
}
