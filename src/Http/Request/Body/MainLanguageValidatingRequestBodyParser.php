<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Request\Body;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\SchemaError;
use JsonPath\JsonObject;
use Psr\Http\Message\ServerRequestInterface;

final class MainLanguageValidatingRequestBodyParser implements RequestBodyParser
{
    private const ORGANIZER_TRANSLATABLE_FIELDS = [
        '$.name',
        '$.address',
        '$.description',
    ];

    private const PLACE_TRANSLATABLE_FIELDS = [
        '$.name',
        '$.description',
        '$.address',
        '$.bookingInfo.urlLabel',
        '$.priceInfo[*].name',
        '$.status.reason',
    ];

    private array $translatableFields;

    /**
     * Translatable fields, as JSON paths so we can use nesting if/when necessary.
     */
    private function __construct(array $translatableFields)
    {
        $this->translatableFields = $translatableFields;
    }

    public function parse(ServerRequestInterface $request): ServerRequestInterface
    {
        $data = $request->getParsedBody();
        if (!is_object($data) ||
            !isset($data->mainLanguage) ||
            !is_string($data->mainLanguage) ||
            $data->mainLanguage === ''
        ) {
            // Will be caught by JSON schema validation.
            return $request;
        }

        $mainLanguage = $data->mainLanguage;
        $errors = [];

        foreach ($this->translatableFields as $translatableField) {
            $jsonObject = new JsonObject($data, true);
            $fieldData = $jsonObject->get($translatableField);

            if (!$fieldData || !is_array($fieldData)) {
                // Either the field is not required and not present, or it is required but not present but it will be
                // handled by the JSON schema validation, or it is present but in an unexpected type which will also be
                // handled by the JSON schema validation.
                continue;
            }

            if (!str_contains($translatableField, '[*]')) {
                if (!isset($fieldData[$mainLanguage])) {
                    $errors[] = new SchemaError(
                        $this->jsonPathToJsonPointer($translatableField),
                        'A value in the mainLanguage (' . $mainLanguage . ') is required.'
                    );
                }

                continue;
            }

            foreach ($fieldData as $fieldIndex => $fieldValue) {
                if (!isset($fieldValue[$mainLanguage])) {
                    $errors[] = new SchemaError(
                        $this->jsonPathToJsonPointer($translatableField, $fieldIndex),
                        'A value in the mainLanguage (' . $mainLanguage . ') is required.'
                    );
                }
            }
        }

        if (count($errors) > 0) {
            throw ApiProblem::bodyInvalidData(...$errors);
        }

        return $request;
    }

    private function jsonPathToJsonPointer(string $jsonPath, int $index = null): string
    {
        $jsonPointer = $jsonPath;

        if ($index !== null) {
            $jsonPointer = str_replace('[*]', '/' . $index, $jsonPointer);
        }

        return str_replace(['$', '.'], ['', '/'], $jsonPointer);
    }

    public static function createForOrganizer(): self
    {
        return new self(self::ORGANIZER_TRANSLATABLE_FIELDS);
    }

    public static function createForPlace(): self
    {
        return new self(self::PLACE_TRANSLATABLE_FIELDS);
    }
}
