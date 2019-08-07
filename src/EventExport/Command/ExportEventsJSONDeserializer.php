<?php

namespace CultuurNet\UDB3\EventExport\Command;

use CultuurNet\Deserializer\JSONDeserializer;
use CultuurNet\Deserializer\MissingValueException;
use CultuurNet\UDB3\EventExport\EventExportQuery;
use CultuurNet\UDB3\EventExport\SapiVersion;
use ValueObjects\StringLiteral\StringLiteral;
use ValueObjects\Web\EmailAddress;

/**
 * @todo Move to udb3-symfony-php.
 * @see https://jira.uitdatabank.be/browse/III-1436
 */
abstract class ExportEventsJSONDeserializer extends JSONDeserializer
{
    private $defaultSapiVersion;

    /**
     * @param $defaultSapiVersion
     */
    public function __construct($defaultSapiVersion)
    {
        parent::__construct();

        $this->defaultSapiVersion = $defaultSapiVersion;
    }

    /**
     * @param StringLiteral $data
     * @return ExportEvents
     */
    public function deserialize(StringLiteral $data)
    {
        $data = parent::deserialize($data);

        if (!isset($data->query)) {
            throw new MissingValueException('query is missing');
        }
        $query = new EventExportQuery($data->query);

        $sapiVersion = $this->defaultSapiVersion;
        if (isset($data->sapiVersion)) {
            $sapiVersion = new SapiVersion($data->sapiVersion);
        }

        $email = $selection = $include = null;
        // @todo This throws an exception when the e-mail is invalid. How do we handle this?
        if (isset($data->email)) {
            $email = new EmailAddress($data->email);
        }

        if (isset($data->selection)) {
            $selection = $data->selection;
        }

        if (isset($data->include)) {
            $include = $data->include;
        }

        return $this->createCommand(
            $query,
            $sapiVersion,
            $email,
            $selection,
            $include
        );
    }

    /**
     * @param EventExportQuery $query
     * @param SapiVersion $sapiVersion
     * @param EmailAddress|null $address
     * @param string[]|null $selection
     * @param string[]|null $include
     * @return ExportEvents
     */
    abstract protected function createCommand(
        EventExportQuery $query,
        SapiVersion $sapiVersion,
        EmailAddress $address = null,
        $selection = null,
        $include = null
    );
}
