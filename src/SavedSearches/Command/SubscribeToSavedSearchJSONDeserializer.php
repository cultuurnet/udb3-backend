<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\SavedSearches\Command;

use CultuurNet\UDB3\Deserializer\JSONDeserializer;
use CultuurNet\UDB3\Deserializer\MissingValueException;
use CultuurNet\UDB3\SavedSearches\Properties\QueryString;
use ValueObjects\StringLiteral\StringLiteral;

/**
 * @todo Move to udb3-symfony-php.
 * @see https://jira.uitdatabank.be/browse/III-1436
 */
class SubscribeToSavedSearchJSONDeserializer extends JSONDeserializer
{
    /**
     * @var StringLiteral
     */
    protected $userId;


    public function __construct(
        StringLiteral $userId
    ) {
        parent::__construct();
        $this->userId = $userId;
    }

    /**
     * @return SubscribeToSavedSearch|\stdClass
     */
    public function deserialize(StringLiteral $data)
    {
        $json = parent::deserialize($data);

        if (!isset($json->name)) {
            throw new MissingValueException('name is missing');
        }

        if (!isset($json->query)) {
            throw new MissingValueException('query is missing');
        }

        return new SubscribeToSavedSearch(
            $this->userId,
            new StringLiteral($json->name),
            new QueryString($json->query)
        );
    }
}
