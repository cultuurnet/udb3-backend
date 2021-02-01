<?php

namespace CultuurNet\UDB3\Cdb\CdbId;

use CultuurNet\UDB3\Cdb\ExternalId\ArrayMappingService;
use PHPUnit\Framework\TestCase;

class EventCdbIdExtractorTest extends TestCase
{
    /**
     * @var ArrayMappingService
     */
    private $placeExternalIdMappingService;

    /**
     * @var ArrayMappingService
     */
    private $organizerExternalIdMappingService;

    /**
     * @var EventCdbIdExtractor
     */
    private $cdbIdExtractor;

    public function setUp()
    {
        $this->placeExternalIdMappingService = new ArrayMappingService(
            [
                'external-id-1' => '9434513c-0f86-4085-83ac-dc4b64b44185',
            ]
        );

        $this->organizerExternalIdMappingService = new ArrayMappingService(
            [
                'external-id-1' => '46573cf5-d279-4baf-8ad4-9e7d7f312100',
            ]
        );

        $this->cdbIdExtractor = new EventCdbIdExtractor(
            $this->placeExternalIdMappingService,
            $this->organizerExternalIdMappingService
        );
    }

    /**
     * @test
     */
    public function it_can_be_instantiated_without_external_id_mapping_services()
    {
        $cdbIdExtractor = new EventCdbIdExtractor();
        $this->assertInstanceOf(EventCdbIdExtractor::class, $cdbIdExtractor);
    }

    /**
     * @test
     */
    public function it_returns_null_if_the_event_has_no_location()
    {
        $cdbEvent = new \CultureFeed_Cdb_Item_Event();
        $this->assertNull($this->cdbIdExtractor->getRelatedPlaceCdbId($cdbEvent));
    }

    /**
     * @test
     */
    public function it_returns_null_if_the_event_has_no_organiser()
    {
        $cdbEvent = new \CultureFeed_Cdb_Item_Event();
        $this->assertNull($this->cdbIdExtractor->getRelatedOrganizerCdbId($cdbEvent));
    }

    /**
     * @test
     */
    public function it_returns_the_cdbid_attribute_if_the_related_location_has_one_on_its_label()
    {
        $locationCdbId = 'bd9768b5-598a-43a3-9acc-bd7c4b3092f8';

        $cdbLocation = new \CultureFeed_Cdb_Data_Location(
            new \CultureFeed_Cdb_Data_Address()
        );
        $cdbLocation->setCdbid($locationCdbId);

        $cdbEvent = new \CultureFeed_Cdb_Item_Event();
        $cdbEvent->setLocation($cdbLocation);

        $this->assertEquals($locationCdbId, $this->cdbIdExtractor->getRelatedPlaceCdbId($cdbEvent));
    }

    /**
     * @test
     */
    public function it_returns_the_cdbid_attribute_if_the_related_organiser_has_one_on_its_label()
    {
        $organiserCdbId = 'bd9768b5-598a-43a3-9acc-bd7c4b3092f8';

        $cdbOrganiser = new \CultureFeed_Cdb_Data_Organiser();
        $cdbOrganiser->setCdbid($organiserCdbId);

        $cdbEvent = new \CultureFeed_Cdb_Item_Event();
        $cdbEvent->setOrganiser($cdbOrganiser);

        $this->assertEquals($organiserCdbId, $this->cdbIdExtractor->getRelatedOrganizerCdbId($cdbEvent));
    }

    /**
     * @test
     */
    public function it_returns_a_cdbid_derived_from_the_external_id_if_the_related_location_has_one_on_its_label()
    {
        $locationExternalId = 'external-id-1';
        $locationCdbId = '9434513c-0f86-4085-83ac-dc4b64b44185';

        $cdbLocation = new \CultureFeed_Cdb_Data_Location(
            new \CultureFeed_Cdb_Data_Address()
        );
        $cdbLocation->setExternalId($locationExternalId);

        $cdbEvent = new \CultureFeed_Cdb_Item_Event();
        $cdbEvent->setLocation($cdbLocation);

        $this->assertEquals($locationCdbId, $this->cdbIdExtractor->getRelatedPlaceCdbId($cdbEvent));
    }

    /**
     * @test
     */
    public function it_returns_a_cdbid_derived_from_the_external_id_if_the_related_organiser_has_one_on_its_label()
    {
        $organiserExternalId = 'external-id-1';
        $organiserCdbId = '46573cf5-d279-4baf-8ad4-9e7d7f312100';

        $cdbOrganiser = new \CultureFeed_Cdb_Data_Organiser();
        $cdbOrganiser->setExternalId($organiserExternalId);

        $cdbEvent = new \CultureFeed_Cdb_Item_Event();
        $cdbEvent->setOrganiser($cdbOrganiser);

        $this->assertEquals($organiserCdbId, $this->cdbIdExtractor->getRelatedOrganizerCdbId($cdbEvent));
    }

    /**
     * @test
     */
    public function it_returns_the_cdbid_attribute_if_the_related_location_has_one_on_its_actor()
    {
        $locationCdbId = 'bd9768b5-598a-43a3-9acc-bd7c4b3092f8';

        $cdbActor = new \CultureFeed_Cdb_Item_Actor();
        $cdbActor->setCdbId($locationCdbId);

        $cdbLocation = new \CultureFeed_Cdb_Data_Location(
            new \CultureFeed_Cdb_Data_Address()
        );
        $cdbLocation->setActor($cdbActor);

        $cdbEvent = new \CultureFeed_Cdb_Item_Event();
        $cdbEvent->setLocation($cdbLocation);

        $this->assertEquals($locationCdbId, $this->cdbIdExtractor->getRelatedPlaceCdbId($cdbEvent));
    }

    /**
     * @test
     */
    public function it_returns_the_cdbid_attribute_if_the_related_organiser_has_one_on_its_actor()
    {
        $organiserCdbId = '46573cf5-d279-4baf-8ad4-9e7d7f312100';

        $cdbActor = new \CultureFeed_Cdb_Item_Actor();
        $cdbActor->setCdbId($organiserCdbId);

        $cdbOrganiser = new \CultureFeed_Cdb_Data_Organiser();
        $cdbOrganiser->setActor($cdbActor);

        $cdbEvent = new \CultureFeed_Cdb_Item_Event();
        $cdbEvent->setOrganiser($cdbOrganiser);

        $this->assertEquals($organiserCdbId, $this->cdbIdExtractor->getRelatedOrganizerCdbId($cdbEvent));
    }

    /**
     * @test
     */
    public function it_returns_a_cdbid_derived_from_the_external_id_if_the_related_location_has_one_on_its_actor()
    {
        $locationExternalId = 'external-id-1';
        $locationCdbId = '9434513c-0f86-4085-83ac-dc4b64b44185';

        $cdbActor = new \CultureFeed_Cdb_Item_Actor();
        $cdbActor->setExternalId($locationExternalId);

        $cdbLocation = new \CultureFeed_Cdb_Data_Location(
            new \CultureFeed_Cdb_Data_Address()
        );
        $cdbLocation->setActor($cdbActor);

        $cdbEvent = new \CultureFeed_Cdb_Item_Event();
        $cdbEvent->setLocation($cdbLocation);

        $this->assertEquals($locationCdbId, $this->cdbIdExtractor->getRelatedPlaceCdbId($cdbEvent));
    }

    /**
     * @test
     */
    public function it_returns_a_cdbid_derived_from_the_external_id_if_the_related_organiser_has_one_on_its_actor()
    {
        $organiserExternalId = 'external-id-1';
        $organiserCdbId = '46573cf5-d279-4baf-8ad4-9e7d7f312100';

        $cdbActor = new \CultureFeed_Cdb_Item_Actor();
        $cdbActor->setExternalId($organiserExternalId);

        $cdbOrganiser = new \CultureFeed_Cdb_Data_Organiser();
        $cdbOrganiser->setActor($cdbActor);

        $cdbEvent = new \CultureFeed_Cdb_Item_Event();
        $cdbEvent->setOrganiser($cdbOrganiser);

        $this->assertEquals($organiserCdbId, $this->cdbIdExtractor->getRelatedOrganizerCdbId($cdbEvent));
    }

    /**
     * @test
     */
    public function it_returns_null_if_the_event_has_a_location_without_cdbid_or_external_id_on_label_or_actor()
    {
        $cdbLocation = new \CultureFeed_Cdb_Data_Location(
            new \CultureFeed_Cdb_Data_Address()
        );

        $cdbEvent = new \CultureFeed_Cdb_Item_Event();
        $cdbEvent->setLocation($cdbLocation);

        $this->assertNull($this->cdbIdExtractor->getRelatedPlaceCdbId($cdbEvent));
    }

    /**
     * @test
     */
    public function it_returns_null_if_the_event_has_an_organiser_without_cdbid_or_external_id_on_label_or_actor()
    {
        $cdbOrganiser = new \CultureFeed_Cdb_Data_Organiser();

        $cdbEvent = new \CultureFeed_Cdb_Item_Event();
        $cdbEvent->setOrganiser($cdbOrganiser);

        $this->assertNull($this->cdbIdExtractor->getRelatedOrganizerCdbId($cdbEvent));
    }
}
