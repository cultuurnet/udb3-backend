<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Request\Body;

use InvalidArgumentException;
use Opis\JsonSchema\Resolvers\SchemaResolver;
use Opis\JsonSchema\Uri;
use Opis\JsonSchema\Validator;
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
        if (!in_array($schemaFileName, self::getConstants(), true)) {
            throw new InvalidArgumentException(
                $schemaFileName . ' is not in the list of known schema files, please use a predefined constant on the JsonSchemaLocator class (or add one).'
            );
        }

        $schemaFileName = ltrim($schemaFileName, '/');
        $schemaFileLocation = self::getSchemaDirectory() . '/' . $schemaFileName;
        if (!is_file($schemaFileLocation)) {
            throw new RuntimeException($schemaFileLocation . ' is not a file.');
        }

        return Uri::create(self::getSchemaDirectoryUri() . $schemaFileName);
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

    private static function getConstants(): array
    {
        return (new ReflectionClass(self::class))->getConstants();
    }
}
