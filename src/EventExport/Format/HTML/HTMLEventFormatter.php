<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventExport\Format\HTML;

use CultuurNet\UDB3\Event\EventType;
use CultuurNet\UDB3\Event\ReadModel\JSONLD\Specifications\EventSpecificationInterface;
use CultuurNet\UDB3\Event\ReadModel\JSONLD\Specifications\Has1Taalicoon;
use CultuurNet\UDB3\Event\ReadModel\JSONLD\Specifications\Has2Taaliconen;
use CultuurNet\UDB3\Event\ReadModel\JSONLD\Specifications\Has3Taaliconen;
use CultuurNet\UDB3\Event\ReadModel\JSONLD\Specifications\Has4Taaliconen;
use CultuurNet\UDB3\Event\ReadModel\JSONLD\Specifications\HasUiTPASBrand;
use CultuurNet\UDB3\Event\ReadModel\JSONLD\Specifications\HasVliegBrand;
use CultuurNet\UDB3\Event\ValueObjects\LocationId;
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
use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\Model\ValueObject\Calendar\CalendarType;
use CultuurNet\UDB3\StringFilter\CombinedStringFilter;
use CultuurNet\UDB3\StringFilter\StripHtmlStringFilter;
use CultuurNet\UDB3\StringFilter\TruncateStringFilter;
use stdClass;

class HTMLEventFormatter
{
    protected CombinedStringFilter $filters;

    /**
     * @var EventSpecificationInterface[]
     */
    protected array $taalicoonSpecs;

    /**
     * @var EventSpecificationInterface[]
     */
    protected array $brandSpecs;

    protected ?EventInfoServiceInterface $uitpas = null;

    protected PriceFormatter $priceFormatter;

    protected UitpasInfoFormatter $uitpasInfoFormatter;

    protected ?CalendarSummaryRepositoryInterface $calendarSummaryRepository = null;

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

        $this->taalicoonSpecs = [
            TaalicoonDescription::eenTaalicoon()->toString() => new Has1Taalicoon(),
            TaalicoonDescription::tweeTaaliconen()->toString() => new Has2Taaliconen(),
            TaalicoonDescription::drieTaaliconen()->toString() => new Has3Taaliconen(),
            TaalicoonDescription::vierTaaliconen()->toString() => new Has4Taaliconen(),
        ];

        $this->brandSpecs = [
            'uitpas' => new HasUiTPASBrand(),
            'vlieg' => new HasVliegBrand(),
        ];
    }

    public function formatEvent(string $eventId, string $eventString): array
    {
        $event = Json::decode($eventString);

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

            $address['isDummyAddress'] = false;
            if (isset($event->location->isDummyPlaceForEducationEvents)) {
                $address['isDummyAddress'] = (bool) $event->location->isDummyPlaceForEducationEvents;
            }
            if (isset($event->location->{'@id'}) &&
                $event->location->{'@id'} !== null &&
                (new LocationId($event->location->{'@id'}))->isNilLocation()) {
                $address['isDummyAddress'] = true;
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
     */
    private function addCalendarInfo(string $eventId, stdClass $event, array &$formattedEvent): void
    {
        if ($this->calendarSummaryRepository) {
            try {
                $calendarType = new CalendarType($event->calendarType);
                $calendarSummaryFormat = $calendarType->sameAs(CalendarType::multiple()) ? Format::sm() : Format::lg();
                $calendarSummary = $this->calendarSummaryRepository->get($eventId, ContentType::html(), $calendarSummaryFormat);
            } catch (SummaryUnavailableException $exception) {
                //TODO: Log the missing summaries.
            };
        }

        $formattedEvent['dates'] = $calendarSummary ?? $event->calendarSummary;
    }

    private function addUitpasInfo(string $eventId, array &$formattedEvent): void
    {
        if ($this->uitpas) {
            $uitpasInfo = $this->uitpas->getEventInfo($eventId);
            $formattedEvent['uitpas'] = $this->uitpasInfoFormatter->format($uitpasInfo);
        }
    }

    private function formatTaaliconen(stdClass $event, array &$formattedEvent): void
    {
        $taalicoonCount = 0;
        $description = '';
        $i = 0;
        $satisfiedCount = 0;

        /** @var EventSpecificationInterface $spec */
        foreach ($this->taalicoonSpecs as $name => $spec) {
            $i++;
            if ($spec->isSatisfiedBy($event)) {
                $satisfiedCount++;
                $taalicoonCount = $i;
                $description = $name;
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
     * @return string[]
     */
    private function getBrands(stdClass $event): array
    {
        return array_keys(
            array_filter(
                $this->brandSpecs,
                function (EventSpecificationInterface $brandSpec) use ($event) {
                    return $brandSpec->isSatisfiedBy($event);
                }
            )
        );
    }

    private function addPriceInfo(stdClass $event, array &$formattedEvent): void
    {
        $basePrice = null;

        if (property_exists($event, 'priceInfo') && is_array($event->priceInfo)) {
            foreach ($event->priceInfo as $price) {
                if ($price->category === 'base') {
                    $basePrice = $price;
                    break;
                }
            }
        }

        $formattedEvent['price'] =
            $basePrice ? $this->priceFormatter->format((float) $basePrice->price) : 'Niet ingevoerd';
    }

    private function addMediaObject(stdClass $event, array &$formattedEvent): void
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
    */
    private function getAddressField(stdClass $event, string $addressField): string
    {
        if (isset($event->location->address->{$addressField})) {
            return $event->location->address->{$addressField};
        }

        $mainLanguage = $event->mainLanguage ?? 'nl';

        return $event->location->address->{$mainLanguage}->{$addressField} ?? '';
    }
}
