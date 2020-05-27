<?php

namespace CultuurNet\UDB3\SavedSearches\Command;

use CultuurNet\Deserializer\JSONDeserializer;
use CultuurNet\Deserializer\MissingValueException;
use CultuurNet\UDB3\SavedSearches\Properties\QueryString;
use CultuurNet\UDB3\ValueObject\SapiVersion;
use ValueObjects\StringLiteral\StringLiteral;

/**
 * @todo Move to udb3-symfony-php.
 * @see https://jira.uitdatabank.be/browse/III-1436
 */
class SubscribeToSavedSearchJSONDeserializer extends JSONDeserializer
{
    /**
     * @var SapiVersion
     */
    protected $sapiVersion;

    /**
     * @var StringLiteral $userId
     */
    protected $userId;

    /**
     * @param SapiVersion $sapiVersion
     * @param StringLiteral $userId
     */
    public function __construct(
        SapiVersion $sapiVersion,
        StringLiteral $userId
    ) {
        parent::__construct();

        $this->sapiVersion = $sapiVersion;
        $this->userId = $userId;
    }

    /**
     * @param StringLiteral $data
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
            $this->sapiVersion,
            $this->userId,
            new StringLiteral($json->name),
            new QueryString($json->query)
        );
    }
}
