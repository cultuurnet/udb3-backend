<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\ReadModel\JSONLD;

use CultureFeed_Cdb_Data_PerformerList;
use CultuurNet\UDB3\CalendarFactoryInterface;
use CultuurNet\UDB3\Cdb\CdbId\EventCdbIdExtractorInterface;
use CultuurNet\UDB3\Cdb\Description\MergedDescription;
use CultuurNet\UDB3\Event\ValueObjects\Audience;
use CultuurNet\UDB3\Event\ValueObjects\AudienceType;
use CultuurNet\UDB3\LabelImporter;
use CultuurNet\UDB3\Offer\ReadModel\JSONLD\CdbXmlContactInfoImporterInterface;
use CultuurNet\UDB3\Offer\ReadModel\JSONLD\CdbXMLItemBaseImporter;
use CultuurNet\UDB3\SluggerInterface;

/**
 * Takes care of importing cultural events in the CdbXML format (UDB2)
 * into a UDB3 JSON-LD document.
 */
class CdbXMLImporter
{
    /**
     * @var CdbXMLItemBaseImporter
     */
    private $cdbXMLItemBaseImporter;

    /**
     * @var EventCdbIdExtractorInterface
     */
    private $cdbIdExtractor;

    /**
     * @var CalendarFactoryInterface
     */
    private $calendarFactory;

    /**
     * @var CdbXmlContactInfoImporterInterface
     */
    private $cdbXmlContactInfoImporter;


    public function __construct(
        CdbXMLItemBaseImporter $cdbXMLItemBaseImporter,
        EventCdbIdExtractorInterface $cdbIdExtractor,
        CalendarFactoryInterface $calendarFactory,
        CdbXmlContactInfoImporterInterface $cdbXmlContactInfoImporter
    ) {
        $this->cdbXMLItemBaseImporter = $cdbXMLItemBaseImporter;
        $this->cdbIdExtractor = $cdbIdExtractor;
        $this->calendarFactory = $calendarFactory;
        $this->cdbXmlContactInfoImporter = $cdbXmlContactInfoImporter;
    }

    /**
     * Imports a UDB2 event into a UDB3 JSON-LD document.
     *
     * @param \stdClass $base
     *   The JSON-LD document to start from.
     * @param \CultureFeed_Cdb_Item_Event $event
     *   The cultural event data from UDB2 to import.
     * @param PlaceServiceInterface $placeManager
     *   The manager from which to retrieve the JSON-LD of a place.
     * @param OrganizerServiceInterface $organizerManager
     *   The manager from which to retrieve the JSON-LD of an organizer.
     * @param SluggerInterface $slugger
     *   The slugger that's used to generate a sameAs reference.
     *
     * @return \stdClass
     *   The document with the UDB2 event data merged in.
     */
    public function documentWithCdbXML(
        $base,
        \CultureFeed_Cdb_Item_Event $event,
        PlaceServiceInterface $placeManager,
        OrganizerServiceInterface $organizerManager,
        SluggerInterface $slugger
    ) {
        $jsonLD = clone $base;

        $detail = null;

        $details = $event->getDetails();

        foreach ($details as $languageDetail) {
            $language = $languageDetail->getLanguage();

            // The first language detail found will be used to retrieve
            // properties from which in UDB3 are not any longer considered
            // to be language specific.
            if (!$detail) {
                $detail = $languageDetail;
            }

            $jsonLD->name[$language] = $languageDetail->getTitle();

            $this->importDescription($languageDetail, $jsonLD, $language);
        }

        $this->cdbXMLItemBaseImporter->importAvailable($event, $jsonLD);

        $labelImporter = new LabelImporter();
        $labelImporter->importLabels($event, $jsonLD);

        $this->importLocation($event, $placeManager, $jsonLD);

        $this->importOrganizer($event, $organizerManager, $jsonLD);

        if ($event->getContactInfo()) {
            $this->cdbXmlContactInfoImporter->importBookingInfo(
                $jsonLD,
                $event->getContactInfo(),
                $detail->getPrice(),
                $event->getBookingPeriod()
            );

            $this->cdbXmlContactInfoImporter->importContactPoint(
                $jsonLD,
                $event->getContactInfo()
            );
        }

        $this->cdbXMLItemBaseImporter->importPriceInfo($details, $jsonLD);

        $this->importTerms($event, $jsonLD);

        $this->cdbXMLItemBaseImporter->importPublicationInfo($event, $jsonLD);

        $calendar = $this->calendarFactory->createFromCdbCalendar($event->getCalendar());
        $jsonLD = (object)array_merge((array)$jsonLD, $calendar->toJsonLd());

        $this->importTypicalAgeRange($event, $jsonLD);

        $this->importPerformers($detail, $jsonLD);

        $this->importUitInVlaanderenReference($event, $slugger, $jsonLD);

        $this->cdbXMLItemBaseImporter->importExternalId($event, $jsonLD);

        $this->importSeeAlso($event, $jsonLD);

        $this->cdbXMLItemBaseImporter->importWorkflowStatus($event, $jsonLD);

        $this->importAudience($event, $jsonLD);

        return $jsonLD;
    }

    /**
     * @param \CultureFeed_Cdb_Data_EventDetail $languageDetail
     * @param \stdClass $jsonLD
     * @param string $language
     */
    private function importDescription($languageDetail, $jsonLD, $language)
    {
        try {
            $description = MergedDescription::fromCdbDetail($languageDetail);
            $jsonLD->description[$language] = $description->toNative();
        } catch (\InvalidArgumentException $e) {
            return;
        }
    }

    /**
     * @param \stdClass $jsonLD
     */
    private function importLocation(\CultureFeed_Cdb_Item_Event $event, PlaceServiceInterface $placeManager, $jsonLD)
    {
        $location = [];
        $location['@type'] = 'Place';

        $locationId = $this->cdbIdExtractor->getRelatedPlaceCdbId($event);

        if ($locationId) {
            $location += $placeManager->placeJSONLD($locationId);
        } else {
            $locationCdb = $event->getLocation();
            $location['mainLanguage'] = 'nl';
            $location['name']['nl'] = $locationCdb->getLabel();
            $address = $locationCdb->getAddress()->getPhysicalAddress();
            if ($address) {
                $location['address']['nl'] = [
                    'addressCountry' => $address->getCountry(),
                    'addressLocality' => $address->getCity(),
                    'postalCode' => $address->getZip(),
                    'streetAddress' => $address->getStreet() . ' ' . $address->getHouseNumber(),
                ];
            }
        }
        $jsonLD->location = $location;
    }

    /**
     * @param \stdClass $jsonLD
     */
    private function importOrganizer(
        \CultureFeed_Cdb_Item_Event $event,
        OrganizerServiceInterface $organizerManager,
        $jsonLD
    ) {
        $organizer = null;
        $organizerId = $this->cdbIdExtractor->getRelatedOrganizerCdbId($event);
        $organizerCdb = $event->getOrganiser();
        $contactInfoCdb = $event->getContactInfo();

        if ($organizerId) {
            $organizer = $organizerManager->organizerJSONLD($organizerId);
        } elseif ($organizerCdb && $contactInfoCdb) {
            $organizer = [];
            $organizer['mainLanguage'] = 'nl';
            $organizer['name']['nl'] = $organizerCdb->getLabel();

            $emailsCdb = $contactInfoCdb->getMails();
            if (count($emailsCdb) > 0) {
                $organizer['email'] = [];
                foreach ($emailsCdb as $email) {
                    $organizer['email'][] = $email->getMailAddress();
                }
            }

            /** @var \CultureFeed_Cdb_Data_Phone[] $phonesCdb */
            $phonesCdb = $contactInfoCdb->getPhones();
            if (count($phonesCdb) > 0) {
                $organizer['phone'] = [];
                foreach ($phonesCdb as $phone) {
                    $organizer['phone'][] = $phone->getNumber();
                }
            }
        }

        if (!is_null($organizer)) {
            $organizer['@type'] = 'Organizer';
            $jsonLD->organizer = $organizer;
        }
    }

    /**
     * @param \stdClass $jsonLD
     */
    private function importTerms(\CultureFeed_Cdb_Item_Event $event, $jsonLD)
    {
        $themeBlacklist = [
            'Thema onbepaald',
            'Meerder kunstvormen',
            'Meerdere filmgenres',
        ];
        $categories = [];
        foreach ($event->getCategories() as $category) {
            /* @var \Culturefeed_Cdb_Data_Category $category */
            if ($category && !in_array($category->getName(), $themeBlacklist)) {
                $categories[] = [
                    'label' => $category->getName(),
                    'domain' => $category->getType(),
                    'id' => $category->getId(),
                ];
            }
        }
        $jsonLD->terms = $categories;
    }

    /**
     * @param \stdClass $jsonLD
     */
    private function importTypicalAgeRange(\CultureFeed_Cdb_Item_Event $event, $jsonLD)
    {
        $ageFrom = $event->getAgeFrom();
        $ageTo = $event->getAgeTo();

        if (!is_int($ageFrom) && !is_int($ageTo)) {
            return;
        }

        $jsonLD->typicalAgeRange = "{$ageFrom}-{$ageTo}";
    }

    /**
     * @param \stdClass $jsonLD
     */
    private function importPerformers(\CultureFeed_Cdb_Data_EventDetail $detail, $jsonLD)
    {
        /** @var CultureFeed_Cdb_Data_PerformerList|null $performers */
        $performers = $detail->getPerformers();
        if ($performers) {
            /** @var \CultureFeed_Cdb_Data_Performer $performer */
            foreach ($performers as $performer) {
                if ($performer->getLabel()) {
                    $performerData = new \stdClass();
                    $performerData->name = $performer->getLabel();
                    $jsonLD->performer[] = $performerData;
                }
            }
        }
    }


    private function importSeeAlso(
        \CultureFeed_Cdb_Item_Event $event,
        \stdClass $jsonLD
    ) {
        if (!property_exists($jsonLD, 'seeAlso')) {
            $jsonLD->seeAlso = [];
        }

        // Add contact info url, if it's not for reservations.
        if ($contactInfo = $event->getContactInfo()) {
            /** @var \CultureFeed_Cdb_Data_Url[] $contactUrls */
            $contactUrls = $contactInfo->getUrls();
            if (is_array($contactUrls) && count($contactUrls) > 0) {
                foreach ($contactUrls as $contactUrl) {
                    if (!$contactUrl->isForReservations()) {
                        $jsonLD->seeAlso[] = $contactUrl->getUrl();
                    }
                }
            }
        }
    }

    /**
     * @param \stdClass $jsonLD
     */
    private function importUitInVlaanderenReference(
        \CultureFeed_Cdb_Item_Event $event,
        SluggerInterface $slugger,
        $jsonLD
    ) {

        // Some events seem to not have a Dutch name, even though this is
        // required. If there's no Dutch name, we just leave the slug empty as
        // that seems to be the behaviour on http://m.uitinvlaanderen.be
        if (isset($jsonLD->name['nl'])) {
            $name = $jsonLD->name['nl'];
            $slug = $slugger->slug($name);
        } else {
            $slug = '';
        }

        $reference = 'http://www.uitinvlaanderen.be/agenda/e/' . $slug . '/' . $event->getCdbId();


        if (!property_exists($jsonLD, 'sameAs')) {
            $jsonLD->sameAs = [];
        }

        if (!in_array($reference, $jsonLD->sameAs)) {
            array_push($jsonLD->sameAs, $reference);
        }
    }


    private function importAudience(\CultureFeed_Cdb_Item_Event $event, \stdClass $jsonLD)
    {
        $eventIsPrivate = (bool) $event->isPrivate();
        $eventTargetsEducation = $eventIsPrivate && $event->getCategories()->hasCategory('2.1.3.0.0');

        $audienceType = $eventTargetsEducation ? 'education' : ($eventIsPrivate ? 'members' : 'everyone');
        $audience = new Audience(AudienceType::fromNative($audienceType));

        $jsonLD->audience = $audience->serialize();
    }
}
