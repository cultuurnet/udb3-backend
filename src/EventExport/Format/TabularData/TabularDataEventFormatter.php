<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventExport\Format\TabularData;

use Closure;
use CommerceGuys\Intl\Currency\CurrencyRepository;
use CommerceGuys\Intl\Currency\CurrencyRepositoryInterface;
use CommerceGuys\Intl\Formatter\NumberFormatter;
use CommerceGuys\Intl\Formatter\NumberFormatterInterface;
use CommerceGuys\Intl\NumberFormat\NumberFormatRepository;
use CultuurNet\UDB3\EventExport\CalendarSummary\CalendarSummaryRepositoryInterface;
use CultuurNet\UDB3\EventExport\CalendarSummary\ContentType;
use CultuurNet\UDB3\EventExport\CalendarSummary\Format;
use CultuurNet\UDB3\EventExport\CalendarSummary\SummaryUnavailableException;
use CultuurNet\UDB3\EventExport\Format\HTML\Uitpas\EventInfo\EventInfoServiceInterface;
use CultuurNet\UDB3\EventExport\Media\MediaFinder;
use CultuurNet\UDB3\EventExport\Media\Url;
use CultuurNet\UDB3\EventExport\PriceFormatter;
use CultuurNet\UDB3\EventExport\UitpasInfoFormatter;
use CultuurNet\UDB3\StringFilter\StripHtmlStringFilter;
use stdClass;

class TabularDataEventFormatter
{
    /**
     * Class used to filter out HTML from strings.
     *
     * @var StripHtmlStringFilter
     */
    protected $htmlFilter;

    /**
     * A list of all included properties
     *
     * @var string[]
     */
    protected $includedProperties;

    /**
     * @var UitpasInfoFormatter
     */
    protected $uitpasInfoFormatter;

    /**
     * @var EventInfoServiceInterface|null
     */
    protected $uitpas;

    /**
     * @var CalendarSummaryRepositoryInterface|null
     */
    protected $calendarSummaryRepository;

    /**
     * @var NumberFormatterInterface
     */
    protected $currencyFormatter;

    /**
     * @var NumberFormatterInterface
     */
    protected $basePriceFormatter;

    /**
     * @var CurrencyRepositoryInterface
     */
    protected $currencyRepository;

    /**
     * @param string[]                                $include                   A list of properties to include
     */
    public function __construct(
        array $include,
        EventInfoServiceInterface $uitpas = null,
        ?CalendarSummaryRepositoryInterface $calendarSummaryRepository = null
    ) {
        $this->htmlFilter = new StripHtmlStringFilter();
        $this->includedProperties = $this->includedOrDefaultProperties($include);
        $this->uitpas = $uitpas;
        $this->uitpasInfoFormatter = new UitpasInfoFormatter(new PriceFormatter(2, ',', '.', 'Gratis'));
        $this->calendarSummaryRepository = $calendarSummaryRepository;

        $numberFormat = (new NumberFormatRepository())->get('nl-BE');
        $this->basePriceFormatter = (new NumberFormatter($numberFormat))->setMinimumFractionDigits(2);
        $this->currencyFormatter = new NumberFormatter($numberFormat, NumberFormatter::CURRENCY);
        $this->currencyRepository = new CurrencyRepository();
    }

    public function formatHeader()
    {
        $columns = [];
        foreach ($this->includedProperties as $property) {
            $columns[] = $this->columns()[$property]['name'];
        }

        return $columns;
    }

    public function formatEvent($event)
    {
        $event = json_decode($event);
        $includedProperties = $this->includedProperties;
        $row = $this->emptyRow();

        foreach ($includedProperties as $property) {
            $column = $this->columns()[$property];
            $value = $column['include']($event);

            if ($value) {
                $row[$property] = $value;
            } else {
                $row[$property] = '';
            }
        }

        return $row;
    }

    /**
     * @param string $date Date in ISO 8601 format.
     * @return string Date formatted for tabular data export.
     */
    protected function formatDate($date)
    {
        $timezoneUtc = new \DateTimeZone('UTC');
        $timezoneBrussels = new \DateTimeZone('Europe/Brussels');

        // Try to create from various formats to maintain backwards
        // compatibility with external systems with older json-ld
        // projections (eg. OMD).
        $formats = [\DateTime::ATOM, \DateTime::ISO8601, 'Y-m-d\TH:i:s'];

        do {
            $datetime = \DateTime::createFromFormat(current($formats), $date, $timezoneUtc);
        } while ($datetime === false && next($formats));

        if ($datetime instanceof \DateTime) {
            $datetime->setTimezone($timezoneBrussels);
            return $datetime->format('Y-m-d H:i');
        } else {
            return '';
        }
    }

    /**
     * @param string $date Date in ISO 8601 format.
     * @return string Date formatted for tabular data export.
     */
    protected function formatDateWithoutTime($date)
    {
        $timezone = new \DateTimeZone('Europe/Brussels');
        $datetime = \DateTime::createFromFormat(\DateTime::ATOM, $date, $timezone);
        return $datetime->format('Y-m-d');
    }

    public function emptyRow()
    {
        $row = [];

        foreach ($this->includedProperties as $property) {
            $row[$property] = '';
        }

        return $row;
    }

    protected function expandMultiColumnProperties($properties)
    {
        $expandedProperties = [];

        $expansions = [
            'address' => [
                'address.streetAddress',
                'address.postalCode',
                'address.addressLocality',
                'address.addressCountry',
            ],
            'contactPoint' => [
                'contactPoint.email',
                'contactPoint.phone',
                'contactPoint.url',
            ],
            'bookingInfo' => [
                'bookingInfo.url',
                'bookingInfo.phone',
                'bookingInfo.email',
            ],
            'image' => [
                'image.url',
                'image.description',
                'image.copyrightHolder',
            ],
            'priceInfo' => [
                'priceInfo.base',
                'priceInfo.all',
            ],
            'labels' => [
                'labels.visible',
                'labels.hidden',
            ],
            'calendarSummary' => [
                'calendarSummary.short',
                'calendarSummary.long',
            ],
        ];

        foreach ($properties as $property) {
            if (isset($expansions[$property])) {
                $expandedProperties = array_merge($expandedProperties, $expansions[$property]);
            } else {
                $expandedProperties[] = $property;
            }
        }

        return $expandedProperties;
    }

    protected function includedOrDefaultProperties($include)
    {
        if ($include) {
            $properties = $this->expandMultiColumnProperties($include);

            array_unshift($properties, 'id');
        } else {
            $properties = array_keys($this->columns());
        }

        return $properties;
    }

    protected function columns()
    {
        $formatter = $this;
        $contactPoint = function (\stdClass $event) use ($formatter) {
            return $formatter->contactPoint($event);
        };

        return [
            'id' => [
                'name' => 'id',
                'include' => function ($event) {
                    return $this->parseEventIdFromUrl($event);
                },
                'property' => 'id',
            ],
            'name' => [
                'name' => 'titel',
                'include' => function ($event) {
                    if ($event->name) {
                        return reset($event->name);
                    }
                },
                'property' => 'name',
            ],
            'creator' => [
                'name' => 'auteur',
                'include' => function ($event) {
                    return $event->creator;
                },
                'property' => 'creator',
            ],
            'priceInfo.base' => [
                'name' => 'basistarief',
                'include' => function ($event) {
                    $basePrice = null;

                    if (property_exists($event, 'priceInfo') && is_array($event->priceInfo)) {
                        foreach ($event->priceInfo as $price) {
                            if ($price->category == 'base') {
                                $basePrice = $price;
                                break;
                            }
                        }
                    }

                    return $basePrice ? $this->basePriceFormatter->format($basePrice->price) : '';
                },
                'property' => 'priceInfo',
            ],
            'priceInfo.all' => [
                'name' => 'prijsinformatie',
                'include' => function ($event) {
                    if (!property_exists($event, 'priceInfo') || !is_array($event->priceInfo)) {
                        return '';
                    }

                    return $this->formatPriceInfo($event->priceInfo, $event->mainLanguage);
                },
                'property' => 'priceInfo',
            ],
            'kansentarief' => [
                'name' => 'kansentarief',
                'include' => function ($event) {
                    $eventUri = $event->{'@id'};
                    $uriParts = explode('/', $eventUri);
                    $eventId = array_pop($uriParts);

                    $uitpasInfo = $this->uitpas->getEventInfo($eventId);
                    if ($uitpasInfo) {
                        $uitpasInfo = $this->uitpasInfoFormatter->format($uitpasInfo);

                        $cardSystems = array_reduce(
                            $uitpasInfo['prices'],
                            function ($cardSystems, $tariff) {
                                $cardSystem = isset($cardSystems[$tariff['cardSystem']])
                                    ? $cardSystems[$tariff['cardSystem']]
                                    : '';
                                $cardSystem = empty($cardSystem)
                                    ? $tariff['cardSystem'] . ': € ' . $tariff['price']
                                    : $cardSystem . ' / € ' . $tariff['price'];

                                $cardSystems[$tariff['cardSystem']] = $cardSystem;
                                return $cardSystems;
                            },
                            []
                        );

                        $formattedTariffs = array_reduce(
                            $cardSystems,
                            function ($tariffs, $cardSystemPrices) {
                                return $tariffs ? $tariffs . ' | ' . $cardSystemPrices : $cardSystemPrices;
                            }
                        );

                        if (!empty($formattedTariffs)) {
                            return $formattedTariffs;
                        }
                    }
                },
                'property' => 'kansentarief',
            ],
            'bookingInfo.url' => [
                'name' => 'reservatie url',
                'include' => $this->includeBookingInfo('url'),
                'property' => 'bookingInfo',
            ],
            'bookingInfo.phone' => [
                'name' => 'reservatie tel',
                'include' => $this->includeBookingInfo('phone'),
                'property' => 'bookingInfo',
            ],
            'bookingInfo.email' => [
                'name' => 'reservatie e-mail',
                'include' => $this->includeBookingInfo('email'),
                'property' => 'bookingInfo',
            ],
            'description' => [
                'name' => 'omschrijving',
                'include' => function ($event) {
                    if (property_exists($event, 'description')) {
                        $description = reset($event->description);

                        // the following preg replace statements will strip unwanted line-breaking characters
                        // except for markup

                        // do not add a whitespace when a line break follows a break tag
                        $description = preg_replace('/<br\ ?\/?>\s+/', '<br>', $description);

                        // replace all leftover line breaks with a space to prevent words from sticking together
                        $description = trim(preg_replace('/\s+/', ' ', $description));

                        return $this->htmlFilter->filter($description);
                    }
                },
                'property' => 'description',
            ],
            'organizer' => [
                'name' => 'organisatie',
                'include' => function ($event) {
                    if (property_exists($event, 'organizer')
                        && isset($event->organizer->name)
                    ) {
                        // @replay_i18n
                        // @see https://jira.uitdatabank.be/browse/III-2201
                        // Should also take into account the main language.
                        if (!is_string($event->organizer->name)) {
                            $mainLanguage = isset($event->mainLanguage) ? $event->mainLanguage : 'nl';
                            return $event->organizer->name->{$mainLanguage};
                        } else {
                            return $event->organizer->name;
                        }
                    }
                },
                'property' => 'organizer',
            ],
            'calendarSummary.short' => [
                'name' => 'korte kalendersamenvatting',
                'include' => $this->calendarSummaryFormatter(Format::MEDIUM(), $this->calendarSummaryRepository),
                'property' => 'calendarSummary',
            ],
            'calendarSummary.long' => [
                'name' => 'lange kalendersamenvatting',
                'include' => $this->calendarSummaryFormatter(Format::LARGE(), $this->calendarSummaryRepository),
                'property' => 'calendarSummary',
            ],
            'labels.visible' => [
                'name' => 'labels',
                'include' => function ($event) {
                    if (isset($event->labels)) {
                        return implode(';', $event->labels);
                    }
                },
                'property' => 'labels',
            ],
            'labels.hidden' => [
                'name' => 'verborgen labels',
                'include' => function ($event) {
                    if (isset($event->hiddenLabels)) {
                        return implode(';', $event->hiddenLabels);
                    }
                },
                'property' => 'labels',
            ],
            'typicalAgeRange' => [
                'name' => 'leeftijd',
                'include' => function ($event) {
                    return $event->typicalAgeRange;
                },
                'property' => 'typicalAgeRange',
            ],
            'performer' => [
                'name' => 'uitvoerders',
                'include' => function ($event) {
                    if (property_exists($event, 'performer')) {
                        $performerNames = [];
                        foreach ($event->performer as $performer) {
                            $performerNames[] = $performer->name;
                        }

                        return implode(';', $performerNames);
                    }
                },
                'property' => 'performer',
            ],
            'language' => [
                'name' => 'taal van het aanbod',
                'include' => function ($event) {
                    if (property_exists($event, 'language')) {
                        return implode(';', $event->language);
                    }
                },
                'property' => 'language',
            ],
            'terms.theme' => [
                'name' => 'thema',
                'include' => function ($event) {
                    if (property_exists($event, 'terms')) {
                        foreach ($event->terms as $term) {
                            if ($term->domain && $term->label && $term->domain == 'theme') {
                                return $term->label;
                            }
                        }
                    }
                },
                'property' => 'terms.theme',
            ],
            'terms.eventtype' => [
                'name' => 'soort aanbod',
                'include' => function ($event) {
                    if (property_exists($event, 'terms')) {
                        foreach ($event->terms as $term) {
                            if ($term->domain && $term->label && $term->domain == 'eventtype') {
                                return $term->label;
                            }
                        }
                    }
                },
                'property' => 'terms.eventtype',
            ],
            'created' => [
                'name' => 'datum aangemaakt',
                'include' => function ($event) {
                    if (!empty($event->created)) {
                        return $this->formatDate($event->created);
                    } else {
                        return '';
                    }
                },
                'property' => 'created',
            ],
            'modified' => [
                'name' => 'datum laatste aanpassing',
                'include' => function ($event) {
                    if (!empty($event->modified)) {
                        return $this->formatDate($event->modified);
                    } else {
                        return '';
                    }
                },
                'property' => 'modified',
            ],
            'available' => [
                'name' => 'embargodatum',
                'include' => function ($event) {
                    if (!empty($event->availableFrom)) {
                        return $this->formatDateWithoutTime($event->availableFrom);
                    } else {
                        return '';
                    }
                },
                'property' => 'available',
            ],
            'startDate' => [
                'name' => 'startdatum',
                'include' => function ($event) {
                    if (!empty($event->startDate)) {
                        return $this->formatDate($event->startDate);
                    } else {
                        return '';
                    }
                },
                'property' => 'startDate',
            ],
            'endDate' => [
                'name' => 'einddatum',
                'include' => function ($event) {
                    if (!empty($event->endDate)) {
                        return $this->formatDate($event->endDate);
                    } else {
                        return '';
                    }
                },
                'property' => 'endDate',
            ],
            'calendarType' => [
                'name' => 'tijd type',
                'include' => function ($event) {
                    return $event->calendarType;
                },
                'property' => 'calendarType',
            ],
            'location' => [
                'name' => 'locatie naam',
                'include' => function ($event) {
                    if (property_exists($event, 'location') && isset($event->location->name)) {
                        return reset($event->location->name);
                    }
                },
                'property' => 'location',
            ],
            'address.streetAddress' => [
                'name' => 'straat',
                'include' => function ($event) {
                    return $this->getAddressField($event, 'streetAddress');
                },
                'property' => 'address.streetAddress',
            ],
            'address.postalCode' => [
                'name' => 'postcode',
                'include' => function ($event) {
                    return $this->getAddressField($event, 'postalCode');
                },
                'property' => 'address.postalCode',
            ],
            'address.addressLocality' => [
                'name' => 'gemeente',
                'include' => function ($event) {
                    return $this->getAddressField($event, 'addressLocality');
                },
                'property' => 'address.addressLocality',
            ],
            'address.addressCountry' => [
                'name' => 'land',
                'include' => function ($event) {
                    return $this->getAddressField($event, 'addressCountry');
                },
                'property' => 'address.addressCountry',
            ],
            'image.url' => [
                'name' => 'afbeelding URL',
                'include' => $this->includeMainImageInfo('contentUrl'),
                'property' => 'image',
            ],
            'image.description' => [
                'name' => 'afbeelding beschrijving',
                'include' => $this->includeMainImageInfo('description'),
                'property' => 'image',
            ],
            'image.copyrightHolder' => [
                'name' => 'afbeelding copyright',
                'include' => $this->includeMainImageInfo('copyrightHolder'),
                'property' => 'image',
            ],
            'sameAs' => [
                'name' => 'externe ids',
                'include' => function ($event) {
                    if (property_exists($event, 'sameAs')) {
                        $ids = [];

                        foreach ($event->sameAs as $externalId) {
                            $ids[] = $externalId;
                        }

                        return implode("\r\n", $ids);
                    }
                },
                'property' => 'sameAs',
            ],
            'contactPoint.email' => [
                'name' => 'contact e-mail',
                'include' => function ($event) use ($contactPoint) {
                    return $this->listJsonldProperty(
                        $contactPoint($event),
                        'email'
                    );
                },
                'property' => 'contactPoint',
            ],
            'contactPoint.phone' => [
                'name' => 'contact tel',
                'include' => function ($event) use ($contactPoint) {
                    return $this->listJsonldProperty(
                        $contactPoint($event),
                        'phone'
                    );
                },
                'property' => 'contactPoint',
            ],
            'contactPoint.url' => [
                'name' => 'contact url',
                'include' => function ($event) use ($contactPoint) {
                    return $this->listJsonldProperty(
                        $contactPoint($event),
                        'url'
                    );
                },
                'property' => 'contactPoint',
            ],
            'contactPoint.reservations.email' => [
                'name' => 'e-mail reservaties',
                'include' => function () {
                    return '';
                },
                'property' => 'contactPoint',
            ],
            'contactPoint.reservations.telephone' => [
                'name' => 'telefoon reservaties',
                'include' => function () {
                    return '';
                },
                'property' => 'contactPoint',
            ],
            'contactPoint.reservations.url' => [
                'name' => 'online reservaties',
                'include' => function () {
                    return '';
                },
                'property' => 'contactPoint',
            ],
            'audience' => [
                'name' => 'toegang',
                'include' => function ($event) {
                    $audienceType = property_exists($event, 'audience') ? $event->audience->audienceType : 'everyone';
                    $toegangTypes = [
                        'everyone' => 'Voor iedereen',
                        'members' => 'Enkel voor leden',
                        'education' => 'Specifiek voor scholen',
                    ];

                    $toegang = $toegangTypes['everyone'];

                    if (array_key_exists($audienceType, $toegangTypes)) {
                        $toegang = $toegangTypes[$audienceType];
                    }

                    return $toegang;
                },
                'property' => 'audience',
            ],
        ];
    }

    /**
     * @param object $jsonldData
     *  An object that contains the jsonld data.
     *
     * @param string $propertyName
     *  The name of the property that contains an array of values.
     *
     * @return string
     */
    private function listJsonldProperty($jsonldData, $propertyName)
    {
        if (property_exists($jsonldData, $propertyName)) {
            return implode(';', $jsonldData->{$propertyName});
        } else {
            return '';
        }
    }

    /**
     * @param object $event
     * @return object
     */
    private function contactPoint($event)
    {
        if (property_exists($event, 'contactPoint')) {
            return $event->contactPoint;
        }

        return new \stdClass();
    }

    /**
     * @param string $propertyName
     * @return Closure
     */
    private function includeBookingInfo($propertyName)
    {
        return function ($event) use ($propertyName) {
            if (property_exists($event, 'bookingInfo')) {
                $bookingInfo = $event->bookingInfo;
                if (is_object($bookingInfo) && property_exists($bookingInfo, $propertyName)) {
                    return $bookingInfo->{$propertyName};
                }
            }
        };
    }

    private function formatPriceInfo(array $priceInfo, string $language): string
    {
        return implode('; ', array_map(function ($tariff) use ($language) {
            return $this->formatTariff($tariff, $language);
        }, $priceInfo));
    }

    private function formatTariff($tariff, string $language)
    {
        $price = (float) $tariff->price;

        $currencyCode = $tariff->priceCurrency;
        $currency = $this->currencyRepository->get($currencyCode);

        $tariffPrice = $this->currencyFormatter->formatCurrency((string) $price, $currency);

        $tariffName = $tariff->name->{$language};

        return $tariffName . ': ' . $tariffPrice;
    }

    /**
     * @param string $propertyName
     * @return Closure
     */
    private function includeMainImageInfo($propertyName)
    {
        return function ($event) use ($propertyName) {
            if (!property_exists($event, 'image') || !property_exists($event, 'mediaObject')) {
                return '';
            }
            $mainImage = (new MediaFinder(new Url($event->image)))->find($event->mediaObject);
            return $mainImage ? $mainImage->{$propertyName} : '';
        };
    }

    private function parseEventIdFromUrl($event): string
    {
        $eventUri = $event->{'@id'};
        $uriParts = explode('/', $eventUri);
        $eventId = array_pop($uriParts);

        return $eventId;
    }

    /**
     * Gives a formatter that tries to fetch a summary in plain text.
     * If the formatted summary is missing, the summary that is available on the event will be used as fallback.
     */
    private function calendarSummaryFormatter(
        Format $format,
        ?CalendarSummaryRepositoryInterface $calendarSummaryRepository = null
    ): Closure {
        return function ($event) use ($calendarSummaryRepository, $format) {
            $eventId = $this->parseEventIdFromUrl($event);

            if ($calendarSummaryRepository) {
                try {
                    $calendarSummary = $calendarSummaryRepository->get($eventId, ContentType::PLAIN(), $format);
                } catch (SummaryUnavailableException $exception) {
                    //TODO: Log the missing summaries.
                };
            }

            return $calendarSummary ?? $event->calendarSummary;
        };
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
