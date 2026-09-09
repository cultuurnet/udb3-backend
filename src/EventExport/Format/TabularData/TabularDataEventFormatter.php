<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventExport\Format\TabularData;

use Closure;
use CommerceGuys\Intl\Currency\CurrencyRepository;
use CommerceGuys\Intl\Currency\CurrencyRepositoryInterface;
use CommerceGuys\Intl\Formatter\NumberFormatter;
use CommerceGuys\Intl\Formatter\NumberFormatterInterface;
use CommerceGuys\Intl\NumberFormat\NumberFormatRepository;
use CultuurNet\UDB3\DateTimeFactory;
use CultuurNet\UDB3\EventExport\AgeRangeFactory;
use CultuurNet\UDB3\EventExport\BirthdateRangeFactory;
use CultuurNet\UDB3\EventExport\CalendarSummary\CalendarSummaryRepositoryInterface;
use CultuurNet\UDB3\EventExport\CalendarSummary\ContentType;
use CultuurNet\UDB3\EventExport\CalendarSummary\Format;
use CultuurNet\UDB3\EventExport\Format\HTML\Uitpas\EventInfo\EventInfoServiceInterface;
use CultuurNet\UDB3\EventExport\Media\MediaFinder;
use CultuurNet\UDB3\EventExport\Media\Url;
use CultuurNet\UDB3\EventExport\OvernightStay;
use CultuurNet\UDB3\EventExport\PriceFormatter;
use CultuurNet\UDB3\EventExport\TargetAudienceDescription;
use CultuurNet\UDB3\EventExport\UitpasInfoFormatter;
use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\StringFilter\StripHtmlStringFilter;
use DateTimeInterface;
use Exception;
use stdClass;

class TabularDataEventFormatter
{
    protected StripHtmlStringFilter $htmlFilter;

    /**
     * A list of all included properties
     *
     * @var string[]
     */
    protected array $includedProperties;

    protected UitpasInfoFormatter $uitpasInfoFormatter;

    protected ?EventInfoServiceInterface $uitpas = null;

    protected ?CalendarSummaryRepositoryInterface $calendarSummaryRepository = null;

    protected NumberFormatterInterface $currencyFormatter;

    protected NumberFormatterInterface $basePriceFormatter;

    protected CurrencyRepositoryInterface $currencyRepository;

    /**
     * @param string[] $include
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

    public function formatHeader(): array
    {
        $columns = [];
        foreach ($this->includedProperties as $property) {
            $columns[] = $this->columns()[$property]['name'];
        }

        return $columns;
    }

    /**
     * The columns that hold a value of more than one line, as column numbers, so that a writer can
     * render those lines. A column is named here rather than recognised by its content, because a
     * newline in a description is markup that has always been shown as a single run of text.
     *
     * @return int[]
     */
    public function wrappedColumns(): array
    {
        $columns = $this->columns();
        $wrapped = [];

        foreach (array_values($this->includedProperties) as $index => $property) {
            if ($columns[$property]['wrap'] ?? false) {
                $wrapped[] = $index + 1;
            }
        }

        return $wrapped;
    }

    public function formatEvent(string $event): array
    {
        $event = Json::decode($event);
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

    protected function formatDate(string $date): string
    {
        $timezoneUtc = new \DateTimeZone('UTC');
        $timezoneBrussels = new \DateTimeZone('Europe/Brussels');

        // Try to create from various formats to maintain backwards
        // compatibility with external systems with older json-ld
        // projections (eg. OMD).
        $formats = [DateTimeInterface::ATOM, DateTimeInterface::ATOM, 'Y-m-d\TH:i:s'];

        do {
            $datetime = \DateTime::createFromFormat(current($formats), $date, $timezoneUtc);
        } while ($datetime === false && next($formats));

        if ($datetime instanceof \DateTime) {
            $datetime->setTimezone($timezoneBrussels);
            return $datetime->format('Y-m-d H:i');
        }

        return '';
    }

    protected function formatDateWithoutTime(string $date): string
    {
        $timezone = new \DateTimeZone('Europe/Brussels');
        $datetime = DateTimeFactory::fromAtom($date)->setTimezone($timezone);
        return $datetime->format('Y-m-d');
    }

    public function emptyRow(): array
    {
        $row = [];

        foreach ($this->includedProperties as $property) {
            $row[$property] = '';
        }

        return $row;
    }

    protected function expandMultiColumnProperties(array $properties): array
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
            'videos' => [
                'videos.url',
                'videos.copyrightHolder',
            ],
            'attendance' => [
                'attendance.mode',
                'attendance.url',
            ],
            // An event carries either a typicalAgeRange or a birthdateRange, and the leeftijd
            // column renders whichever one it has, so both include values name that one column.
            'birthdateRange' => [
                'typicalAgeRange',
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

    protected function includedOrDefaultProperties(?array $include): array
    {
        if ($include) {
            $properties = $this->expandMultiColumnProperties($include);

            array_unshift($properties, 'id');

            // Asking for the typicalAgeRange and the birthdateRange both name the leeftijd column,
            // and an id the export prepends anyway can also be asked for, so a property that is
            // named twice still becomes a single column.
            $properties = array_values(array_unique($properties));
        } else {
            $properties = array_keys($this->columns());
        }

        return $properties;
    }

    protected function columns(): array
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
                            if ($price->category === 'base') {
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

                    $uitpasInfo = $this->uitpasInfoFormatter->format(
                        $this->uitpas->getEventInfo($eventId)
                    );

                    $cardSystems = array_reduce(
                        $uitpasInfo['prices'],
                        function ($cardSystems, $tariff) {
                            $cardSystem = $cardSystems[$tariff['cardSystem']] ?? '';
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
                    /** @var stdClass $event */
                    if (isset($event->organizer, $event->organizer->name)) {
                        $name = (array) $event->organizer->name;
                        return $name[$this->getMainLanguage($event)] ?? current($name);
                    }
                    return '';
                },
                'property' => 'organizer',
            ],
            'calendarSummary.short' => [
                'name' => 'korte kalendersamenvatting',
                'include' => $this->calendarSummaryFormatter(Format::md(), $this->calendarSummaryRepository),
                'property' => 'calendarSummary',
            ],
            'calendarSummary.long' => [
                'name' => 'lange kalendersamenvatting',
                'include' => $this->calendarSummaryFormatter(Format::lg(), $this->calendarSummaryRepository),
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
                    return $this->formatAgeRange($event);
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
                            if ($term->domain && $term->label && $term->domain === 'theme') {
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
                            if ($term->label && $term->domain === 'eventtype') {
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
                    }

                    return '';
                },
                'property' => 'created',
            ],
            'modified' => [
                'name' => 'datum laatste aanpassing',
                'include' => function ($event) {
                    if (!empty($event->modified)) {
                        return $this->formatDate($event->modified);
                    }

                    return '';
                },
                'property' => 'modified',
            ],
            'available' => [
                'name' => 'embargodatum',
                'include' => function ($event) {
                    if (!empty($event->availableFrom)) {
                        return $this->formatDateWithoutTime($event->availableFrom);
                    }

                    return '';
                },
                'property' => 'available',
            ],
            'startDate' => [
                'name' => 'startdatum',
                'include' => function ($event) {
                    if (!empty($event->startDate)) {
                        return $this->formatDate($event->startDate);
                    }

                    return '';
                },
                'property' => 'startDate',
            ],
            'endDate' => [
                'name' => 'einddatum',
                'include' => function ($event) {
                    if (!empty($event->endDate)) {
                        return $this->formatDate($event->endDate);
                    }

                    return '';
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
            'status' => [
                'name' => 'status',
                'include' => function ($event) {
                    return $this->formatStatus($event->status);
                },
                'property' => 'status',
            ],
            'bookingAvailability' => [
                'name' => 'tickets & plaatsen',
                'include' => function ($event) {
                    return $this->formatBookingAvailability($event->bookingAvailability);
                },
                'property' => 'bookingAvailability',
            ],
            'videos.url' => [
                'name' => 'videos URL',
                'include' => function ($event) {
                    return $this->formatVideo($event, 'url');
                },
                'property' => 'videos',
            ],
            'videos.copyrightHolder' => [
                'name' => 'videos copyright',
                'include' => function ($event) {
                    return $this->formatVideo($event, 'copyrightHolder');
                },
                'property' => 'videos',
            ],
            'attendance.mode' => [
                'name' => 'Aanwezigheidsvorm (fysiek / online)',
                'include' => fn ($event) => $this->formatAttendanceMode($event->attendanceMode),
                'property' => 'attendanceMode',
            ],
            'attendance.url' => [
                'name' => 'online url',
                'include' => fn ($event) => $event->onlineUrl ?? '',
                'property' => 'onlineUrl',
            ],
            'completeness' => [
                'name' => 'Volledigheid',
                'include' => function ($event) {
                    return $event->completeness ?? '';
                },
                'property' => 'completeness',
            ],
            'faqs' => [
                'name' => 'faq',
                'include' => function ($event) {
                    return $this->formatFaqs($event);
                },
                'property' => 'faqs',
                'wrap' => true,
            ],
            'hasOvernightStay' => [
                'name' => 'met overnachting',
                'include' => function ($event) {
                    return $this->formatOvernightStay($event);
                },
                'property' => 'subEvent',
            ],
            'childrenOnly' => [
                'name' => 'doelgroep',
                'include' => fn ($event) => TargetAudienceDescription::fromEvent($event),
                'property' => 'childrenOnly',
            ],
        ];
    }

    private function listJsonldProperty(object $jsonldData, string $propertyName): string
    {
        if (property_exists($jsonldData, $propertyName)) {
            return implode(';', $jsonldData->{$propertyName});
        }

        return '';
    }

    private function contactPoint(object $event): object
    {
        if (property_exists($event, 'contactPoint')) {
            return $event->contactPoint;
        }

        return new \stdClass();
    }

    private function includeBookingInfo(string $propertyName): Closure
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

    private function formatTariff(\stdClass $tariff, string $language): string
    {
        $price = (float) $tariff->price;

        $currencyCode = $tariff->priceCurrency;
        $currency = $this->currencyRepository->get($currencyCode);

        $tariffPrice = $this->currencyFormatter->formatCurrency((string) $price, $currency);

        $tariffName = $tariff->name->{$language};

        return $tariffName . ': ' . $tariffPrice;
    }

    private function includeMainImageInfo(string $propertyName): Closure
    {
        return function ($event) use ($propertyName) {
            if (!property_exists($event, 'image') || !property_exists($event, 'mediaObject')) {
                return '';
            }
            $mainImage = (new MediaFinder(new Url($event->image)))->find($event->mediaObject);
            return $mainImage ? $mainImage->{$propertyName} : '';
        };
    }

    private function parseEventIdFromUrl(stdClass $event): string
    {
        $eventUri = $event->{'@id'};
        $uriParts = explode('/', $eventUri);
        return array_pop($uriParts);
    }

    /**
     * Gives a formatter that tries to fetch a summary in plain text.
     */
    private function calendarSummaryFormatter(
        Format $format,
        ?CalendarSummaryRepositoryInterface $calendarSummaryRepository = null
    ): Closure {
        return function ($event) use ($calendarSummaryRepository, $format) {
            $eventId = $this->parseEventIdFromUrl($event);

            if ($calendarSummaryRepository) {
                try {
                    return $calendarSummaryRepository->get($eventId, ContentType::plain(), $format);
                } catch (Exception $exception) {
                    return '';
                }
            }
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

        return $event->location->address->{$this->getMainLanguage($event)}->{$addressField} ?? '';
    }

    private function getMainLanguage(stdClass $event): string
    {
        return $event->mainLanguage ?? 'nl';
    }

    private function formatFaqs(stdClass $event): string
    {
        if (!isset($event->faqs) || !is_array($event->faqs)) {
            return '';
        }

        $items = [];

        foreach ($event->faqs as $faq) {
            if (!$faq instanceof stdClass) {
                continue;
            }

            $translation = $this->pickTranslation(get_object_vars($faq), $this->getMainLanguage($event));

            if ($translation === null) {
                continue;
            }

            $items[] = $this->toSingleLine($translation->question) . ' ' .
                $this->toSingleLine($translation->answer);
        }

        return implode("\n", $items);
    }

    /**
     * An item that was never translated to the main language of the event falls back to whatever
     * translation it does have, so that no question disappears from the export.
     *
     * A question or an answer that is not text at all can still turn up in an older projection.
     * Such a translation is passed over instead of handed to a string parameter, where it would
     * raise a TypeError that fails the export of the whole result set.
     */
    private function pickTranslation(array $translations, string $mainLanguage): ?stdClass
    {
        if (isset($translations[$mainLanguage])) {
            // Keys on the left win and keep their position, so the main language moves to the front
            // and every other language keeps its projection order.
            $translations = [$mainLanguage => $translations[$mainLanguage]] + $translations;
        }

        foreach ($translations as $candidate) {
            if ($candidate instanceof stdClass
                && isset($candidate->question, $candidate->answer)
                && is_string($candidate->question)
                && is_string($candidate->answer)
            ) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * Questions and answers are free text that can contain markup, just like a description, so the
     * tags are stripped first: the filter turns a <br> or a </p> into newlines, which then collapse
     * into the single space that separates the words.
     */
    private function toSingleLine(string $text): string
    {
        return trim(preg_replace('/\s+/', ' ', $this->htmlFilter->filter($text)));
    }

    /**
     * An event describes its audience with a typicalAgeRange or a birthdateRange, and the polyfill
     * drops the typicalAgeRange of an event that has a birthdate range. An older projection can
     * still carry both, in which case a specific age range wins, just like in the HTML export.
     *
     * @see \CultuurNet\UDB3\EventExport\Format\HTML\HTMLEventFormatter::addAgeRangeInfo()
     */
    private function formatAgeRange(stdClass $event): string
    {
        $typicalAgeRange = isset($event->typicalAgeRange) && is_string($event->typicalAgeRange)
            ? $event->typicalAgeRange
            : '';

        if (AgeRangeFactory::hasSpecificAgeRange($typicalAgeRange)) {
            return $typicalAgeRange;
        }

        // The birthdate range only fills in when there is no specific age range, so for an all ages
        // or a malformed value.
        $birthdateRange = BirthdateRangeFactory::fromJson($event->birthdateRange ?? null);

        if ($birthdateRange !== null) {
            return BirthdateRangeFactory::formatRange($birthdateRange);
        }

        // Without a usable birthdate range the original value is still the best available answer,
        // which keeps exporting "-" for an all ages event.
        return $typicalAgeRange;
    }

    /**
     * The column stays empty for an event type that could never have an overnight stay, instead of
     * claiming there is none.
     */
    private function formatOvernightStay(stdClass $event): string
    {
        $hasOvernightStay = OvernightStay::forEvent($event);

        if ($hasOvernightStay === null) {
            return '';
        }

        return $hasOvernightStay ? 'ja' : 'nee';
    }

    private function formatStatus(stdClass $status): string
    {
        $map = [
            'Available' => 'Gaat door',
            'TemporarilyUnavailable' => 'Uitgesteld',
            'Unavailable' => 'Geannuleerd',
        ];

        if (!array_key_exists($status->type, $map)) {
            return '';
        }

        return $map[$status->type];
    }

    private function formatBookingAvailability(stdClass $bookingAvailability): string
    {
        $map = [
            'Available' => 'Beschikbaar',
            'Unavailable' => 'Volzet of uitverkocht',
        ];

        if (!array_key_exists($bookingAvailability->type, $map)) {
            return '';
        }

        return $map[$bookingAvailability->type];
    }

    private function formatVideo(stdClass $event, string $property): string
    {
        if (!property_exists($event, 'videos') || !is_array($event->videos)) {
            return '';
        }

        $properties = [];
        foreach ($event->videos as $video) {
            $properties[] = $video->{$property};
        }
        return implode(';', $properties);
    }

    private function formatAttendanceMode(string $attendanceMode): string
    {
        $map = [
            'offline' => 'fysiek',
            'online' => 'online',
            'mixed' => 'gemengd (fysiek / online)',
        ];

        return $map[$attendanceMode];
    }
}
