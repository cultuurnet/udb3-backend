<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Completeness;

use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\Model\ValueObject\Identity\ItemType;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use PHPUnit\Framework\TestCase;

final class CompletenessFromWeightsTest extends TestCase
{
    private CompletenessFromWeights $eventCompleteness;
    private CompletenessFromWeights $placeCompleteness;
    private CompletenessFromWeights $organizerCompleteness;

    protected function setUp(): void
    {
        $this->eventCompleteness = new CompletenessFromWeights(
            CompletenessTestConfig::forEvents(),
            ItemType::event()
        );
        $this->placeCompleteness = new CompletenessFromWeights(
            CompletenessTestConfig::forPlaces(),
            ItemType::place()
        );
        $this->organizerCompleteness = new CompletenessFromWeights(
            CompletenessTestConfig::forOrganizers(),
            ItemType::organizer()
        );
    }

    /**
     * @test
     */
    public function it_gives_a_children_only_event_credit_for_capacity_and_remaining_capacity(): void
    {
        $body = $this->aCompleteEventBody() + [
            'childrenOnly' => true,
        ];
        $body['bookingAvailability'] = [
            'type' => 'Available',
            'capacity' => 100,
            'remainingCapacity' => 40,
        ];

        $this->assertSame(100, $this->calculate($this->eventCompleteness, $body));
    }

    /**
     * @test
     */
    public function it_keeps_capacity_weights_in_the_total_for_a_children_only_event_without_capacity(): void
    {
        $body = $this->aCompleteEventBody() + [
            'childrenOnly' => true,
        ];

        $this->assertSame(96, $this->calculate($this->eventCompleteness, $body));
    }

    /**
     * @test
     */
    public function it_lets_a_non_children_only_event_reach_full_completeness_without_capacity(): void
    {
        $this->assertSame(100, $this->calculate($this->eventCompleteness, $this->aCompleteEventBody()));
    }

    /**
     * @test
     */
    public function it_does_not_expect_capacity_for_an_event_with_children_only_set_to_false(): void
    {
        $body = $this->aCompleteEventBody() + [
            'childrenOnly' => false,
        ];

        $this->assertSame(100, $this->calculate($this->eventCompleteness, $body));
    }

    /**
     * @test
     * @dataProvider nonSubEventCalendarTypeDataProvider
     */
    public function it_does_not_expect_capacity_for_a_children_only_event_without_sub_events(string $calendarType): void
    {
        $body = $this->aCompleteEventBody() + [
            'childrenOnly' => true,
        ];
        $body['calendarType'] = $calendarType;

        $this->assertSame(100, $this->calculate($this->eventCompleteness, $body));
    }

    public function nonSubEventCalendarTypeDataProvider(): array
    {
        return [
            'permanent' => ['permanent'],
            'periodic' => ['periodic'],
        ];
    }

    /**
     * @test
     */
    public function it_gives_a_place_credit_for_capacity_inside_booking_availability(): void
    {
        $body = $this->aCompletePlaceBody();
        $body['bookingAvailability'] = [
            'type' => 'Available',
            'capacity' => 100,
        ];

        $this->assertSame(100, $this->calculate($this->placeCompleteness, $body));
    }

    /**
     * @test
     */
    public function it_always_expects_capacity_for_a_place(): void
    {
        $this->assertSame(98, $this->calculate($this->placeCompleteness, $this->aCompletePlaceBody()));
    }

    /**
     * @test
     */
    public function it_accepts_a_birthdate_range_instead_of_a_typical_age_range(): void
    {
        $body = $this->aCompleteEventBody();
        unset($body['typicalAgeRange']);
        $body['birthdateRange'] = '2014-01-01/2020-01-01';

        $this->assertSame(100, $this->calculate($this->eventCompleteness, $body));
    }

    /**
     * @test
     */
    public function it_gives_no_age_range_credit_without_a_typical_age_range_or_birthdate_range(): void
    {
        $body = $this->aCompleteEventBody();
        unset($body['typicalAgeRange']);

        $this->assertSame(87, $this->calculate($this->eventCompleteness, $body));
    }

    /**
     * @test
     */
    public function it_calculates_full_completeness_for_an_organizer(): void
    {
        $this->assertSame(100, $this->calculate($this->organizerCompleteness, $this->aCompleteOrganizerBody()));
    }

    /**
     * @test
     */
    public function it_gives_no_description_credit_for_a_description_of_200_characters_or_less(): void
    {
        $body = $this->aCompleteEventBody();
        $body['description'] = ['nl' => str_repeat('a', 200)];

        $this->assertSame(90, $this->calculate($this->eventCompleteness, $body));
    }

    /**
     * @test
     */
    public function it_gives_no_contact_point_credit_for_an_empty_contact_point(): void
    {
        $body = $this->aCompleteEventBody();
        $body['contactPoint'] = ['phone' => [], 'email' => [], 'url' => []];

        $this->assertSame(97, $this->calculate($this->eventCompleteness, $body));
    }

    private function calculate(CompletenessFromWeights $completeness, array $body): int
    {
        return $completeness->calculateForDocument(
            new JsonDocument('9f50a221-9d9d-4d75-9e35-b06e2b6e879b', Json::encode($body))
        );
    }

    private function aCompleteEventBody(): array
    {
        return [
            'mainLanguage' => 'nl',
            'name' => ['nl' => 'Testevent'],
            'terms' => [
                ['domain' => 'eventtype', 'id' => '0.50.4.0.0', 'label' => 'Concert'],
                ['domain' => 'theme', 'id' => '1.8.1.0.0', 'label' => 'Rock'],
            ],
            'calendarType' => 'single',
            'location' => ['@id' => 'https://io.uitdatabank.dev/places/2fdb2225-58b4-4f5f-a01d-9528cd9e7f36'],
            'typicalAgeRange' => '6-12',
            'mediaObject' => [['@id' => 'https://io.uitdatabank.dev/images/25025aba-ca07-4d24-8b3a-c389683d05a1']],
            'description' => ['nl' => str_repeat('a', 201)],
            'priceInfo' => [['category' => 'base', 'price' => 10]],
            'contactPoint' => ['phone' => ['016 10 20 30'], 'email' => [], 'url' => []],
            'bookingInfo' => ['url' => 'https://www.publiq.be'],
            'faqs' => [['question' => 'Is er parking?', 'answer' => 'Ja']],
            'bookingAvailability' => ['type' => 'Available'],
            'organizer' => ['@id' => 'https://io.uitdatabank.dev/organizers/3fce6d47-4a1a-4c41-b2ff-0bbbebbc4dc0'],
            'videos' => [['id' => '5c1b6ae7-4283-4f27-9be9-5b09b89bda54']],
        ];
    }

    private function aCompletePlaceBody(): array
    {
        return [
            'mainLanguage' => 'nl',
            'name' => ['nl' => 'Testplaats'],
            'terms' => [
                ['domain' => 'eventtype', 'id' => '0.14.0.0.0', 'label' => 'Monument'],
            ],
            'calendarType' => 'permanent',
            'address' => ['nl' => ['streetAddress' => 'Teststraat 1', 'postalCode' => '3000', 'addressLocality' => 'Leuven', 'addressCountry' => 'BE']],
            'typicalAgeRange' => '6-12',
            'mediaObject' => [['@id' => 'https://io.uitdatabank.dev/images/25025aba-ca07-4d24-8b3a-c389683d05a1']],
            'description' => ['nl' => str_repeat('a', 201)],
            'faqs' => [['question' => 'Is er parking?', 'answer' => 'Ja']],
            'priceInfo' => [['category' => 'base', 'price' => 10]],
            'contactPoint' => ['phone' => ['016 10 20 30'], 'email' => [], 'url' => []],
            'bookingInfo' => ['url' => 'https://www.publiq.be'],
            'bookingAvailability' => ['type' => 'Available'],
            'organizer' => ['@id' => 'https://io.uitdatabank.dev/organizers/3fce6d47-4a1a-4c41-b2ff-0bbbebbc4dc0'],
            'videos' => [['id' => '5c1b6ae7-4283-4f27-9be9-5b09b89bda54']],
        ];
    }

    private function aCompleteOrganizerBody(): array
    {
        return [
            'mainLanguage' => 'nl',
            'name' => ['nl' => 'Testorganisatie'],
            'url' => 'https://www.publiq.be',
            'contactPoint' => ['phone' => ['016 10 20 30'], 'email' => [], 'url' => []],
            'description' => ['nl' => str_repeat('a', 201)],
            'images' => [['@id' => 'https://io.uitdatabank.dev/images/25025aba-ca07-4d24-8b3a-c389683d05a1']],
            'address' => ['nl' => ['streetAddress' => 'Teststraat 1', 'postalCode' => '3000', 'addressLocality' => 'Leuven', 'addressCountry' => 'BE']],
        ];
    }
}
