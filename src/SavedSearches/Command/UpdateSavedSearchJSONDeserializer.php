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
final class UpdateSavedSearchJSONDeserializer extends JSONDeserializer
{
    private string $userId;
    private string $id;

    public function __construct(string $userId, string $id)
    {
        parent::__construct();
        $this->userId = $userId;
        $this->id =$id;
    }

    public function deserialize(string $data): UpdateSavedSearch
    {
        $json = parent::deserialize($data);

        if (!isset($json->name)) {
            throw new MissingValueException('name is missing');
        }

        if (!isset($json->query)) {
            throw new MissingValueException('query is missing');
        }

        return new UpdateSavedSearch(
            $this->id,
            $this->userId,
            $json->name,
            new QueryString($json->query)
        );
    }
}
