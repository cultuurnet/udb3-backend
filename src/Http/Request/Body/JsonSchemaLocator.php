<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Request\Body;

use CultuurNet\UDB3\Offer\OfferType;
use InvalidArgumentException;
use Opis\JsonSchema\Resolvers\SchemaResolver;
use Opis\JsonSchema\Uri;
use ReflectionClass;
use RuntimeException;

final class JsonSchemaLocator
{
    private static ?string $schemaDirectory;
    public const EVENT = 'event.json';

    public const EVENT_NAME_PUT = 'event-name-put.json';
    public const EVENT_ATTENDANCE_MODE_PUT = 'event-attendanceMode-put.json';
    public const EVENT_ONLINE_URL_PUT = 'event-onlineUrl-put.json';
    public const EVENT_AUDIENCE = 'event-audience.json';
    public const EVENT_AVAILABLE_FROM_PUT = 'event-availableFrom-put.json';
    public const EVENT_BOOKING_AVAILABILITY = 'event-bookingAvailability.json';
    public const EVENT_BOOKING_INFO = 'event-bookingInfo.json';
    public const EVENT_CALENDAR_PUT = 'event-calendar-put.json';
    public const EVENT_CONTACT_POINT_PUT = 'event-contactPoint-put.json';
    public const EVENT_DESCRIPTION_PUT = 'event-description-put.json';
    public const EVENT_FACILITIES_PUT = 'event-facilities-put.json';
    public const EVENT_IMAGE_POST = 'event-image-post.json';
    public const EVENT_IMAGE_PUT = 'event-image-put.json';
    public const EVENT_MAIN_IMAGE_PUT = 'event-main-image-put.json';
    public const EVENT_PRICE_INFO_PUT = 'event-priceInfo.json';
    public const EVENT_STATUS = 'event-status.json';
    public const EVENT_SUB_EVENT_PATCH = 'event-subEvent-patch.json';
    public const EVENT_TYPICAL_AGE_RANGE_PUT = 'event-typicalAgeRange-put.json';
    public const EVENT_VIDEOS_PATCH = 'event-videos-patch.json';
    public const EVENT_VIDEOS_POST = 'event-videos-post.json';
    public const EVENT_WORKFLOW_STATUS_PUT = 'event-workflowStatus-put.json';
    public const EVENT_CONTRIBUTORS_PUT = 'event-contributors-put.json';

    public const PLACE = 'place.json';
    public const PLACE_ADDRESS_PUT = 'place-address-put.json';
    public const PLACE_NAME_PUT = 'place-name-put.json';
    public const PLACE_AVAILABLE_FROM_PUT = 'place-availableFrom-put.json';
    public const PLACE_BOOKING_AVAILABILITY = 'place-bookingAvailability.json';
    public const PLACE_BOOKING_INFO = 'place-bookingInfo.json';
    public const PLACE_CALENDAR_PUT = 'place-calendar-put.json';
    public const PLACE_CONTACT_POINT_PUT = 'place-contactPoint-put.json';
    public const PLACE_DESCRIPTION_PUT = 'place-description-put.json';
    public const PLACE_FACILITIES_PUT = 'place-facilities-put.json';
    public const PLACE_IMAGE_POST = 'place-image-post.json';
    public const PLACE_IMAGE_PUT = 'place-image-put.json';
    public const PLACE_MAIN_IMAGE_PUT = 'place-main-image-put.json';
    public const PLACE_PRICE_INFO_PUT = 'place-priceInfo.json';
    public const PLACE_STATUS = 'place-status.json';
    public const PLACE_TYPICAL_AGE_RANGE_PUT = 'place-typicalAgeRange-put.json';
    public const PLACE_VIDEOS_PATCH = 'place-videos-patch.json';
    public const PLACE_VIDEOS_POST = 'place-videos-post.json';
    public const PLACE_WORKFLOW_STATUS_PUT = 'place-workflowStatus-put.json';
    public const PLACE_CONTRIBUTORS_PUT = 'place-contributors-put.json';

    public const ORGANIZER = 'organizer.json';
    public const ORGANIZER_NAME_PUT = 'organizer-name-put.json';
    public const ORGANIZER_DESCRIPTION_PUT = 'organizer-description-put.json';
    public const ORGANIZER_EDUCATIONAL_DESCRIPTION_PUT = 'organizer-educational-description-put.json';
    public const ORGANIZER_ADDRESS_PUT = 'organizer-address-put.json';
    public const ORGANIZER_URL_PUT = 'organizer-url-put.json';
    public const ORGANIZER_CONTACT_POINT_PUT = 'organizer-contactPoint-put.json';
    public const ORGANIZER_IMAGE_POST = 'organizer-image-post.json';
    public const ORGANIZER_MAIN_IMAGE_PUT = 'organizer-main-image-put.json';
    public const ORGANIZER_IMAGES_PATCH = 'organizer-images-patch.json';
    public const ORGANIZER_CONTRIBUTORS_PUT = 'organizer-contributors-put.json';

    public const OWNERSHIP_POST = 'ownership-post.json';

    public const NEWS_ARTICLE_POST = 'newsArticle-post.json';

    public static function setSchemaDirectory(string $schemaDirectory): void
    {
        if (!is_dir($schemaDirectory)) {
            throw new InvalidArgumentException($schemaDirectory . ' could not be found or is not a directory.');
        }
        // Use realpath() to resolve symbolic directories like ".." and ".", which is needed for the validator to work.
        self::$schemaDirectory = realpath($schemaDirectory);
    }

    public static function createSchemaResolver(): SchemaResolver
    {
        $resolver = new SchemaResolver();
        $resolver->registerPrefix(self::getSchemaDirectoryUri(), self::getSchemaDirectory());
        return $resolver;
    }

    public static function createSchemaUri(string $schemaFileName): Uri
    {
        // Prevent usages of hardcoded strings so we can easily refactor the file locations later if they ever change.
        self::guardFileNameInKnownConstants($schemaFileName);

        $schemaFileName = ltrim($schemaFileName, '/');
        $schemaFileLocation = self::getSchemaDirectory() . '/' . $schemaFileName;
        if (!is_file($schemaFileLocation)) {
            throw new RuntimeException($schemaFileLocation . ' is not a file.');
        }

        return Uri::create(self::getSchemaDirectoryUri() . $schemaFileName);
    }

    public static function getSchemaFileByOfferType(OfferType $offerType, string $eventSchema, string $placeSchema): string
    {
        // Prevent usages of hardcoded strings so we can easily refactor the file locations later if they ever change.
        self::guardFileNameInKnownConstants($eventSchema);
        self::guardFileNameInKnownConstants($placeSchema);
        if ($offerType->sameAs(OfferType::event())) {
            return $eventSchema;
        }
        if ($offerType->sameAs(OfferType::place())) {
            return $placeSchema;
        }
        throw new RuntimeException('No schema found for unknown offer type ' . $offerType->toString());
    }

    private static function getSchemaDirectory(): string
    {
        if (self::$schemaDirectory === null) {
            throw new RuntimeException(
                'JsonSchemaLocator::setSchemaDirectory() should have been called at least once first.'
            );
        }
        return self::$schemaDirectory;
    }

    private static function getSchemaDirectoryUri(): string
    {
        return 'file://' . self::getSchemaDirectory() . '/';
    }

    private static function guardFileNameInKnownConstants(string $schemaFileName): void
    {
        if (!in_array($schemaFileName, self::getConstants(), true)) {
            throw new InvalidArgumentException(
                $schemaFileName . ' is not in the list of known schema files, please use a predefined constant on the JsonSchemaLocator class (or add one).'
            );
        }
    }

    private static function getConstants(): array
    {
        return (new ReflectionClass(self::class))->getConstants();
    }
}
