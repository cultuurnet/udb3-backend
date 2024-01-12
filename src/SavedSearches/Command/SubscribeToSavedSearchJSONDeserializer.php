<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\SavedSearches\Command;

use CultuurNet\UDB3\Deserializer\JSONDeserializer;
use CultuurNet\UDB3\Deserializer\MissingValueException;
use CultuurNet\UDB3\SavedSearches\Properties\QueryString;

/**
 * @deprecated
 *   Refactor to implement RequestBodyParser and throw ApiProblemException
 */
final class SubscribeToSavedSearchJSONDeserializer extends JSONDeserializer
{
    private string $userId;

    public function __construct(string $userId)
    {
        parent::__construct();
        $this->userId = $userId;
    }

    public function deserialize(string $data): SubscribeToSavedSearch
    {
        $data = parent::deserialize($data);

        if (!isset($data->name)) {
            throw new MissingValueException('name is missing');
        }

        if (!isset($data->query)) {
            throw new MissingValueException('query is missing');
        }

        return new SubscribeToSavedSearch(
            $this->userId,
            $data->name,
            new QueryString($data->query)
        );
    }
}
