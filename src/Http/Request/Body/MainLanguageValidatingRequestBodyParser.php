<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Request\Body;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\SchemaError;
use Opis\JsonSchema\JsonPointer;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;

final class MainLanguageValidatingRequestBodyParser implements RequestBodyParser
{
    private const ORGANIZER_TRANSLATABLE_FIELDS = [
        '/name',
        '/address',
    ];

    private array $translatableFields;

    /**
     * Translatable fields, as JSON pointers so we can use nesting if/when necessary.
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
            $jsonPointer = JsonPointer::parse($translatableField);
            if (!$jsonPointer) {
                throw new RuntimeException('Could not parse JSON pointer ' . $translatableField);
            }

            $fieldData = $jsonPointer->data($data);
            if (!is_object($fieldData)) {
                // Either the field is not required and not present, or it is required but not present but it will be
                // handled by the JSON schema validation, or it is present but in an unexpected type which will also be
                // handled by the JSON schema validation.
                continue;
            }

            if (!isset($fieldData->{$mainLanguage})) {
                $errors[] = new SchemaError($translatableField, 'A value in the mainLanguage (' . $mainLanguage . ') is required.');
            }
        }

        if (count($errors) > 0) {
            throw ApiProblem::bodyInvalidData(...$errors);
        }

        return $request;
    }

    public static function createForOrganizer(): self
    {
        return new self(self::ORGANIZER_TRANSLATABLE_FIELDS);
    }
}
