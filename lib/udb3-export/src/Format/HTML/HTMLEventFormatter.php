<?php

namespace CultuurNet\UDB3\EventExport\Format\HTML;

use CultuurNet\UDB3\Event\EventType;
use CultuurNet\UDB3\Event\ReadModel\JSONLD\Specifications\EventSpecificationInterface;
use CultuurNet\UDB3\Event\ReadModel\JSONLD\Specifications\Has1Taalicoon;
use CultuurNet\UDB3\Event\ReadModel\JSONLD\Specifications\Has2Taaliconen;
use CultuurNet\UDB3\Event\ReadModel\JSONLD\Specifications\Has3Taaliconen;
use CultuurNet\UDB3\Event\ReadModel\JSONLD\Specifications\Has4Taaliconen;
use CultuurNet\UDB3\Event\ReadModel\JSONLD\Specifications\HasUiTPASBrand;
use CultuurNet\UDB3\Event\ReadModel\JSONLD\Specifications\HasVliegBrand;
use CultuurNet\UDB3\EventExport\CalendarSummary\CalendarSummaryRepositoryInterface;
use CultuurNet\UDB3\EventExport\CalendarSummary\ContentType;
use CultuurNet\UDB3\EventExport\CalendarSummary\Format;
use CultuurNet\UDB3\EventExport\CalendarSummary\SummaryUnavailableException;
use CultuurNet\UDB3\EventExport\Format\HTML\Properties\TaalicoonDescription;
use CultuurNet\UDB3\EventExport\Format\HTML\Uitpas\EventInfo\EventInfoServiceInterface;
use CultuurNet\UDB3\EventExport\PriceFormatter;
use CultuurNet\UDB3\EventExport\UitpasInfoFormatter;
use CultuurNet\UDB3\EventExport\Media\MediaFinder;
use CultuurNet\UDB3\EventExport\Media\Url;
use CultuurNet\UDB3\StringFilter\CombinedStringFilter;
use CultuurNet\UDB3\StringFilter\StripHtmlStringFilter;
use CultuurNet\UDB3\StringFilter\TruncateStringFilter;
use stdClass;

class HTMLEventFormatter
{
    /**
     * @var CombinedStringFilter
     */
    protected $filters;

    /**
     * @var EventSpecificationInterface[]
     */
    protected $taalicoonSpecs;

    /**
     * @var EventSpecificationInterface[]
     */
    protected $brandSpecs;

    /**
     * @var EventInfoServiceInterface|null
     */
    protected $uitpas;

    /**
     * @var PriceFormatter
     */
    protected $priceFormatter;

    /**
     * @var UitpasInfoFormatter
     */
    protected $uitpasInfoFormatter;

    /**
     * @var CalendarSummaryRepositoryInterface|null
     */
    protected $calendarSummaryRepository;

    /**
     * @param EventInfoServiceInterface|null $uitpas
     * @param CalendarSummaryRepositoryInterface $calendarSummaryRepository
     */
    public function __construct(
        EventInfoServiceInterface $uitpas = null,
        CalendarSummaryRepositoryInterface $calendarSummaryRepository = null
    ) {
        $this->uitpas = $uitpas;
        $this->calendarSummaryRepository = $calendarSummaryRepository;

        $this->priceFormatter = new PriceFormatter(2, ',', '.', 'Gratis');

        $this->uitpasInfoFormatter = new UitpasInfoFormatter($this->priceFormatter);

        $this->filters = new CombinedStringFilter();

        $this->filters->addFilter(new StripHtmlStringFilter());

        $truncateFilter = new TruncateStringFilter(300);
        $truncateFilter->addEllipsis();
        $truncateFilter->turnOnWordSafe(1);
        $truncateFilter->beSentenceFriendly();
        $this->filters->addFilter($truncateFilter);

        $this->taalicoonSpecs = array(
            'EEN_TAALICOON' => new Has1Taalicoon(),
            'TWEE_TAALICONEN' => new Has2Taaliconen(),
            'DRIE_TAALICONEN' => new Has3Taaliconen(),
            'VIER_TAALICONEN' => new Has4Taaliconen()
        );

        $this->brandSpecs = array(
            'uitpas' => new HasUiTPASBrand(),
            'vlieg' => new HasVliegBrand()
        );
    }

    /**
     * @param string $eventId
     *   The event's CDB ID.
     * @param string $eventString
     *   The cultural event encoded as JSON-LD
     *
     * @return array
     *   The event as an array suitable for rendering with HTMLFileWriter
     */
    public function formatEvent($eventId, $eventString)
    {
        $event = json_decode($eventString);

        $formattedEvent = [];

        if (isset($event->image)) {
            $formattedEvent['image'] = $event->image;
        }

        $type = EventType::fromJSONLDEvent($eventString);
        if ($type) {
            $formattedEvent['type'] = $type->getLabel();
        }

        $formattedEvent['title'] = reset($event->name);

        if (property_exists($event, 'description')) {
            $formattedEvent['description'] = $this->filters->filter(
                reset($event->description)
            );
        }

        $address = [];

        if (property_exists($event, 'location')) {
            if (property_exists($event->location, 'address')) {
                $address += [
                    'street' => $this->getAddressField($event, 'streetAddress'),
                    'postcode' => $this->getAddressField($event, 'postalCode'),
                    'municipality' => $this->getAddressField($event, 'addressLocality'),
                    'country' => $this->getAddressField($event, 'addressCountry'),
                ];

                $address['concatenated'] = implode(' ', $address);
            }

            if (property_exists($event->location, 'name')) {
                $address['name'] = reset($event->location->name);
            }

            if (property_exists($event->location, 'geo')) {
                $address += [
                    'latitude' => $event->location->geo->latitude,
                    'longitude' => $event->location->geo->longitude,
                ];
            }
        }

        if (!empty($address)) {
            $formattedEvent['address'] = $address;
        }

        $this->addPriceInfo($event, $formattedEvent);

        $this->addCalendarInfo($eventId, $event, $formattedEvent);

        $this->addUitpasInfo($eventId, $formattedEvent);

        $this->formatTaaliconen($event, $formattedEvent);

        $formattedEvent['brands'] = $this->getBrands($event);

        if (isset($event->typicalAgeRange)) {
            $ageRange = $event->typicalAgeRange;
            $formattedEvent['ageFrom'] = explode('-', $ageRange)[0];
        }

        $this->addMediaObject($event, $formattedEvent);

        return $formattedEvent;
    }

    /**
     * Adds the calendar info by trying to fetch the large summary.
     * If the large formatted summary is missing, the summary that is available on the event will be used as fallback.
     *
     * @param string $eventId
     * @param stdClass $event
     * @param array $formattedEvent
     */
    private function addCalendarInfo($eventId, stdClass $event, array &$formattedEvent)
    {
        if ($this->calendarSummaryRepository) {
            try {
                $calendarSummary = $this->calendarSummaryRepository->get($eventId, ContentType::HTML(), Format::SMALL());
            } catch (SummaryUnavailableException $exception) {
                //TODO: Log the missing summaries.
            };
        }

        $formattedEvent['dates'] = isset($calendarSummary) ? $calendarSummary : $event->calendarSummary;
    }

    /**
     * @param string $eventId
     * @param array $formattedEvent
     */
    private function addUitpasInfo($eventId, array &$formattedEvent)
    {
        if ($this->uitpas) {
            $uitpasInfo = $this->uitpas->getEventInfo($eventId);
            if ($uitpasInfo) {
                $formattedEvent['uitpas'] = $this->uitpasInfoFormatter->format($uitpasInfo);
            }
        }
    }

    /**
     * @param $event
     * @param $formattedEvent
     */
    private function formatTaaliconen($event, &$formattedEvent)
    {
        $taalicoonCount = 0;
        $description = '';
        $i = 0;
        $satisfiedCount = 0;

        foreach ($this->taalicoonSpecs as $name => $spec) {
            $i++;
            /** @var EventSpecificationInterface $spec */
            if ($spec->isSatisfiedBy($event)) {
                $satisfiedCount++;
                $taalicoonCount = $i;
                $description = TaalicoonDescription::getByName($name)->getValue();
            }
        }

        // Only add the taalicoonCount if the event was tagged with a single "taaliconen" tag. If multiple tags were
        // added, simply ignore the taaliconen.
        if ($taalicoonCount > 0 && $satisfiedCount == 1) {
            $formattedEvent['taalicoonCount'] = $taalicoonCount;
            $formattedEvent['taalicoonDescription'] = $description;
        }
    }

    /**
     * @param $event
     * @return string[]
     */
    private function getBrands($event)
    {
        return array_keys(array_filter(
            $this->brandSpecs,
            function (EventSpecificationInterface $brandSpec) use ($event) {
                return $brandSpec->isSatisfiedBy($event);
            }
        ));
    }

    /**
     * @param stdClass $event
     * @param array $formattedEvent
     */
    private function addPriceInfo($event, &$formattedEvent)
    {
        $basePrice = null;

        if (property_exists($event, 'priceInfo') && is_array($event->priceInfo)) {
            foreach ($event->priceInfo as $price) {
                if ($price->category == 'base') {
                    $basePrice = $price;
                    break;
                }
            }
        }

        $formattedEvent['price'] =
            $basePrice ? $this->priceFormatter->format($basePrice->price) : 'Niet ingevoerd';
    }

    /**
     * @param stdClass $event
     * @param array $formattedEvent
     */
    private function addMediaObject($event, &$formattedEvent)
    {
        if (!property_exists($event, 'image') || !property_exists($event, 'mediaObject')) {
            return;
        }

        $mediaFinder = new MediaFinder(new Url($event->image));
        $mainImage = $mediaFinder->find($event->mediaObject);

        if ($mainImage) {
            $formattedEvent['mediaObject'] = $mainImage;
        }
    }


    /**
    * @replay_i18n
    * @see https://jira.uitdatabank.be/browse/III-2201
    *
    * @param object $event
    * @param string $addressField
    *
    * @return string
    */
    private function getAddressField($event, $addressField)
    {
        if (isset($event->location->address->{$addressField})) {
            return $event->location->address->{$addressField};
        } else {
            $mainLanguage = isset($event->mainLanguage) ? $event->mainLanguage : 'nl';
            if (isset($event->location->address->{$mainLanguage}->{$addressField})) {
                return $event->location->address->{$mainLanguage}->{$addressField};
            }
        }
    }
}
