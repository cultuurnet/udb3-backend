<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http;

use PHPUnit\Framework\TestCase;

class LegacyPathRewriterTest extends TestCase
{
    /**
     * @test
     * @dataProvider rewritesDataProvider
     */
    public function it_rewrites_legacy_paths_and_leaves_others_as_is(
        string $originalPath,
        string $expectedRewrite
    ): void {
        $actualRewrite = (new LegacyPathRewriter())->rewritePath($originalPath);
        $this->assertEquals($expectedRewrite, $actualRewrite);
    }

    public function rewritesDataProvider(): array
    {
        return [
            'event_detail_singular_to_pluralized' => [
                'original' => '/event/ccb8f44b-d316-41f0-aeec-2b4149be6cd3',
                'rewrite' => '/events/ccb8f44b-d316-41f0-aeec-2b4149be6cd3',
            ],
            'place_detail_singular_to_pluralized' => [
                'original' => '/place/ccb8f44b-d316-41f0-aeec-2b4149be6cd3',
                'rewrite' => '/places/ccb8f44b-d316-41f0-aeec-2b4149be6cd3',
            ],
            'event_detail_untouched' => [
                'original' => '/events/ccb8f44b-d316-41f0-aeec-2b4149be6cd3',
                'rewrite' => '/events/ccb8f44b-d316-41f0-aeec-2b4149be6cd3',
            ],
            'place_detail_untouched' => [
                'original' => '/places/ccb8f44b-d316-41f0-aeec-2b4149be6cd3',
                'rewrite' => '/places/ccb8f44b-d316-41f0-aeec-2b4149be6cd3',
            ],
            'event_detail_without_prefixed_slash_singular_to_pluralized' => [
                'original' => 'event/ccb8f44b-d316-41f0-aeec-2b4149be6cd3',
                'rewrite' => 'events/ccb8f44b-d316-41f0-aeec-2b4149be6cd3',
            ],
            'place_detail_without_prefixed_slash_singular_to_pluralized' => [
                'original' => 'place/ccb8f44b-d316-41f0-aeec-2b4149be6cd3',
                'rewrite' => 'places/ccb8f44b-d316-41f0-aeec-2b4149be6cd3',
            ],
            'event_detail_without_prefixed_slash_untouched' => [
                'original' => 'events/ccb8f44b-d316-41f0-aeec-2b4149be6cd3',
                'rewrite' => 'events/ccb8f44b-d316-41f0-aeec-2b4149be6cd3',
            ],
            'place_detail_without_prefixed_slash_untouched' => [
                'original' => 'places/ccb8f44b-d316-41f0-aeec-2b4149be6cd3',
                'rewrite' => 'places/ccb8f44b-d316-41f0-aeec-2b4149be6cd3',
            ],
            'event_update_name_singular_to_pluralized' => [
                'original' => '/event/ccb8f44b-d316-41f0-aeec-2b4149be6cd3/name/nl',
                'rewrite' => '/events/ccb8f44b-d316-41f0-aeec-2b4149be6cd3/name/nl',
            ],
            'place_update_name_singular_to_pluralized' => [
                'original' => '/place/ccb8f44b-d316-41f0-aeec-2b4149be6cd3/name/nl',
                'rewrite' => '/places/ccb8f44b-d316-41f0-aeec-2b4149be6cd3/name/nl',
            ],
            'event_update_name_untouched' => [
                'original' => '/events/ccb8f44b-d316-41f0-aeec-2b4149be6cd3/name/nl',
                'rewrite' => '/events/ccb8f44b-d316-41f0-aeec-2b4149be6cd3/name/nl',
            ],
            'place_update_name_untouched' => [
                'original' => '/places/ccb8f44b-d316-41f0-aeec-2b4149be6cd3/name/nl',
                'rewrite' => '/places/ccb8f44b-d316-41f0-aeec-2b4149be6cd3/name/nl',
            ],
            'event_singular_calsum_to_pluralized_calendar_summary' => [
                'original' => '/event/ccb8f44b-d316-41f0-aeec-2b4149be6cd3/calsum',
                'rewrite' => '/events/ccb8f44b-d316-41f0-aeec-2b4149be6cd3/calendar-summary',
            ],
            'place_singular_calsum_to_pluralized_calendar_summary' => [
                'original' => '/place/ccb8f44b-d316-41f0-aeec-2b4149be6cd3/calsum',
                'rewrite' => '/places/ccb8f44b-d316-41f0-aeec-2b4149be6cd3/calendar-summary',
            ],
            'event_calsum_to_calendar_summary' => [
                'original' => '/events/ccb8f44b-d316-41f0-aeec-2b4149be6cd3/calsum',
                'rewrite' => '/events/ccb8f44b-d316-41f0-aeec-2b4149be6cd3/calendar-summary',
            ],
            'place_calsum_to_calendar_summary' => [
                'original' => '/places/ccb8f44b-d316-41f0-aeec-2b4149be6cd3/calsum',
                'rewrite' => '/places/ccb8f44b-d316-41f0-aeec-2b4149be6cd3/calendar-summary',
            ],
            'news_articles_with_underscore_to_hyphen' => [
                'original' => '/news_articles',
                'rewrite' => '/news-articles',
            ],
            'news_articles_detail_with_underscore_to_kebab_case' => [
                'original' => '/news_articles/8a5fcfae-e698-437a-87a5-32cd4ac61076',
                'rewrite' => '/news-articles/8a5fcfae-e698-437a-87a5-32cd4ac61076',
            ],
            'event_update_booking_availability_camel_case_to_kebab_case' => [
                'original' => '/events/8a5fcfae-e698-437a-87a5-32cd4ac61076/bookingAvailability',
                'rewrite' => '/events/8a5fcfae-e698-437a-87a5-32cd4ac61076/booking-availability',
            ],
            'event_update_booking_info_camel_case_to_kebab_case' => [
                'original' => '/events/8a5fcfae-e698-437a-87a5-32cd4ac61076/bookingInfo',
                'rewrite' => '/events/8a5fcfae-e698-437a-87a5-32cd4ac61076/booking-info',
            ],
            'event_update_contact_point_camel_case_to_kebab_case' => [
                'original' => '/events/8a5fcfae-e698-437a-87a5-32cd4ac61076/contactPoint',
                'rewrite' => '/events/8a5fcfae-e698-437a-87a5-32cd4ac61076/contact-point',
            ],
            'event_update_major_info_camel_case_to_kebab_case' => [
                'original' => '/events/8a5fcfae-e698-437a-87a5-32cd4ac61076/majorInfo',
                'rewrite' => '/events/8a5fcfae-e698-437a-87a5-32cd4ac61076/major-info',
            ],
            'event_update_price_info_camel_case_to_kebab_case' => [
                'original' => '/events/8a5fcfae-e698-437a-87a5-32cd4ac61076/priceInfo',
                'rewrite' => '/events/8a5fcfae-e698-437a-87a5-32cd4ac61076/price-info',
            ],
            'event_update_sub_events_camel_case_to_kebab_case' => [
                'original' => '/events/8a5fcfae-e698-437a-87a5-32cd4ac61076/subEvents',
                'rewrite' => '/events/8a5fcfae-e698-437a-87a5-32cd4ac61076/sub-events',
            ],
            'event_update_typical_age_range_camel_case_to_kebab_case' => [
                'original' => '/events/8a5fcfae-e698-437a-87a5-32cd4ac61076/typicalAgeRange',
                'rewrite' => '/events/8a5fcfae-e698-437a-87a5-32cd4ac61076/typical-age-range',
            ],
            'event_uitpas_card_systems_camel_case_to_kebab_case' => [
                'original' => '/uitpas/events/08a70475-4ffe-44b9-b0b9-256c82e7d747/cardSystems',
                'rewrite' => '/uitpas/events/08a70475-4ffe-44b9-b0b9-256c82e7d747/card-systems',
            ],
            'event_uitpas_distribution_keys_camel_case_to_kebab_case' => [
                'original' => '/uitpas/events/08a70475-4ffe-44b9-b0b9-256c82e7d747/cardSystems/1/distributionKey',
                'rewrite' => '/uitpas/events/08a70475-4ffe-44b9-b0b9-256c82e7d747/card-systems/1/distribution-key',
            ],
        ];
    }
}
