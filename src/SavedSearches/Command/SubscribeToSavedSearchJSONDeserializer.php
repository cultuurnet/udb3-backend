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
    private string $id;

    public function __construct(string $id, string $userId)
    {
        parent::__construct();
        $this->id = $id;
        $this->userId = $userId;
    }

    public function deserialize(string $data): SubscribeToSavedSearch
    {
        $json = parent::deserialize($data);

        if (!isset($json->name)) {
            throw new MissingValueException('name is missing');
        }

        if (!isset($json->query)) {
            throw new MissingValueException('query is missing');
        }

        return new SubscribeToSavedSearch(
            $this->id,
            $this->userId,
            $json->name,
            new QueryString($json->query)
        );
    }
}
