<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Completeness;

final class CompletenessTestConfig
{
    public static function forEvents(): Weights
    {
        return Weights::fromConfig([
            'type' => 12,
            'theme' => 5,
            'calendarType' => 12,
            'location' => 12,
            'name' => 12,
            'typicalAgeRange' => 12,
            'mediaObject' => 7,
            'description' => 9,
            'priceInfo' => 6,
            'contactPoint' => 2,
            'bookingInfo' => 2,
            'faqs' => 2,
            'capacity' => 2,
            'remainingCapacity' => 2,
            'organizer' => 2,
            'videos' => 1,
        ]);
    }

    public static function forPlaces(): Weights
    {
        return Weights::fromConfig([
            'type' => 17,
            'calendarType' => 12,
            'address' => 12,
            'name' => 12,
            'typicalAgeRange' => 12,
            'mediaObject' => 9,
            'description' => 9,
            'priceInfo' => 8,
            'contactPoint' => 2,
            'bookingInfo' => 2,
            'capacity' => 2,
            'organizer' => 2,
            'videos' => 1,
        ]);
    }

    public static function forOrganizers(): Weights
    {
        return Weights::fromConfig([
            'name' => 20,
            'url' => 20,
            'contactPoint' => 20,
            'description' => 15,
            'images' => 15,
            'address' => 10,
        ]);
    }
}
