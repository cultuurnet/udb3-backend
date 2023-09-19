<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\SavedSearches\Command;

use CultuurNet\UDB3\Deserializer\JSONDeserializer;
use CultuurNet\UDB3\Deserializer\MissingValueException;
use CultuurNet\UDB3\SavedSearches\Properties\QueryString;
use CultuurNet\UDB3\StringLiteral;

/**
 * @deprecated
 *   Refactor to implement RequestBodyParser and throw ApiProblemException
 */
class SubscribeToSavedSearchJSONDeserializer extends JSONDeserializer
{
    protected StringLiteral $userId;

    public function __construct(StringLiteral $userId)
    {
        parent::__construct();
        $this->userId = $userId;
    }

    public function deserialize(StringLiteral $data): SubscribeToSavedSearch
    {
        $json = parent::deserialize($data);

        if (!isset($json->name)) {
            throw new MissingValueException('name is missing');
        }

        if (!isset($json->query)) {
            throw new MissingValueException('query is missing');
        }

        return new SubscribeToSavedSearch(
            $this->userId->toNative(),
            new StringLiteral($json->name),
            new QueryString($json->query)
        );
    }
}
