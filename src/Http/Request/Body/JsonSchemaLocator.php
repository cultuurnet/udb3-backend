<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Request\Body;

use InvalidArgumentException;
use ReflectionClass;
use RuntimeException;

final class JsonSchemaLocator
{
    private static ?string $schemaDirectory;

    public const EVENT_SUB_EVENT_PATCH = 'event-subEvent-patch.json';

    // For OFFER schemas, use the ones for events
    public const OFFER_BOOKING_AVAILABILITY = 'event-bookingAvailability.json';
    public const OFFER_STATUS = 'event-status.json';

    public static function setSchemaDirectory(string $schemaDirectory): void
    {
        if (!is_dir($schemaDirectory)) {
            throw new InvalidArgumentException($schemaDirectory . ' could not be found or is not a directory.');
        }
        self::$schemaDirectory = rtrim($schemaDirectory, '/');
    }

    public static function loadSchema(string $schemaFileName): string
    {
        if (self::$schemaDirectory === null) {
            throw new RuntimeException(
                'JsonSchemaLocator::setSchemaDirectory() should be called at least once before calling getSchema().'
            );
        }

        // Prevent usages of hardcoded strings so we can easily refactor the file locations later if they ever change.
        if (!in_array($schemaFileName, self::getConstants(), true)) {
            throw new InvalidArgumentException(
                $schemaFileName . ' is not in the list of known schema files, please use a predefined constant on the JsonSchemaLocator class (or add one).'
            );
        }

        $schemaFileLocation = self::$schemaDirectory . '/' . ltrim($schemaFileName, '/');
        if (!is_file($schemaFileLocation)) {
            throw new RuntimeException($schemaFileLocation . ' is not a file.');
        }

        $schema = file_get_contents($schemaFileLocation);
        if ($schema === false) {
            throw new RuntimeException(
                'Could not read ' . $schemaFileLocation
            );
        }

        return $schema;
    }

    private static function getConstants(): array
    {
        return (new ReflectionClass(self::class))->getConstants();
    }
}
