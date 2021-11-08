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

    public const EVENT_AUDIENCE = 'event-audience.json';
    public const EVENT_BOOKING_AVAILABILITY = 'event-bookingAvailability.json';
    public const EVENT_CALENDAR_PUT = 'event-calendar-put.json';
    public const EVENT_FACILITIES_PUT = 'event-facilities-put.json';
    public const EVENT_STATUS = 'event-status.json';
    public const EVENT_SUB_EVENT_PATCH = 'event-subEvent-patch.json';
    public const EVENT_VIDEOS_PATCH = 'event-videos-patch.json';
    public const EVENT_VIDEOS_POST = 'event-videos-post.json';

    public const PLACE_BOOKING_AVAILABILITY = 'place-bookingAvailability.json';
    public const PLACE_CALENDAR_PUT = 'place-calendar-put.json';
    public const PLACE_FACILITIES_PUT = 'place-facilities-put.json';
    public const PLACE_STATUS = 'place-status.json';
    public const PLACE_VIDEOS_PATCH = 'place-videos-patch.json';
    public const PLACE_VIDEOS_POST = 'place-videos-post.json';

    public const ORGANIZER_NAME_PUT = 'organizer-name-put.json';
    public const ORGANIZER_ADDRESS_PUT = 'organizer-address-put.json';
    public const ORGANIZER_URL_PUT = 'organizer-url-put.json';
    public const ORGANIZER_CONTACT_POINT_PUT = 'organizer-contactPoint-put.json';

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
        if ($offerType->sameValueAs(OfferType::EVENT())) {
            return $eventSchema;
        }
        if ($offerType->sameValueAs(OfferType::PLACE())) {
            return $placeSchema;
        }
        throw new RuntimeException('No schema found for unknown offer type ' . $offerType->toNative());
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
