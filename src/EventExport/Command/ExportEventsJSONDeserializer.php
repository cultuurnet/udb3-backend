<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventExport\Command;

use CultuurNet\UDB3\Deserializer\JSONDeserializer;
use CultuurNet\UDB3\Deserializer\MissingValueException;
use CultuurNet\UDB3\EventExport\EventExportQuery;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddress;
use ValueObjects\StringLiteral\StringLiteral;

/**
 * @deprecated
 *   Refactor to implement RequestBodyParser and throw ApiProblemException
 */
abstract class ExportEventsJSONDeserializer extends JSONDeserializer
{
    /**
     * @return ExportEvents
     */
    public function deserialize(StringLiteral $data)
    {
        $data = parent::deserialize($data);

        if (!isset($data->query)) {
            throw new MissingValueException('query is missing');
        }
        $query = new EventExportQuery($data->query);

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
            $email,
            $selection,
            $include
        );
    }

    /**
     * @param string[]|null     $selection
     * @param string[]|null     $include
     * @return ExportEvents
     */
    abstract protected function createCommand(
        EventExportQuery $query,
        EmailAddress $address = null,
        $selection = null,
        $include = null
    );
}
