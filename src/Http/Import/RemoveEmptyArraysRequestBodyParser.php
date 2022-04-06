<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Import;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\SchemaError;
use CultuurNet\UDB3\Http\Request\Body\RequestBodyParser;
use CultuurNet\UDB3\Model\Import\Taxonomy\Category\CategoryResolverInterface;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryID;
use JsonPath\JsonObject;
use Psr\Http\Message\ServerRequestInterface;
use stdClass;

/**
 * Removes properties like labels, hiddenLabels and images that are an empty array. We the rest of the code expect
 * those properties to have at least 1 value, or just not be present.
 */
final class RemoveEmptyArraysRequestBodyParser implements RequestBodyParser
{
    private const ORGANIZER_LIST_FIELDS = [
        'labels',
        'hiddenLabels',
        'images',
    ];

    private const PLACE_LIST_FIELDS = [
        'labels',
        'hiddenLabels',
        'mediaObject',
        'openingHours',
        'priceInfo',
        'videos',
    ];

    private const EVENT_LIST_FIELDS = [
        'labels',
        'hiddenLabels',
        'mediaObject',
        'openingHours',
        'priceInfo',
        'videos',
    ];

    private array $fields;

    private function __construct(array $fields)
    {
        $this->fields = $fields;
    }

    public static function createForOrganizers(): self
    {
        return new self(self::ORGANIZER_LIST_FIELDS);
    }

    public static function createForPlaces(): self
    {
        return new self(self::PLACE_LIST_FIELDS);
    }

    public static function createForEvents(): self
    {
        return new self(self::EVENT_LIST_FIELDS);
    }

    public function parse(ServerRequestInterface $request): ServerRequestInterface
    {
        $json = $request->getParsedBody();
        if (!$json instanceof stdClass) {
            return $request;
        }

        foreach ($this->fields as $field) {
            if (isset($json->$field) && is_array($json->$field) && empty($json->$field)) {
                unset($json->$field);
            }
        }

        return $request->withParsedBody($json);
    }
}
