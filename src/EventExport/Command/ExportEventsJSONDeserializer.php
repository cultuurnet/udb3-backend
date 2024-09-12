<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventExport\Command;

use CultuurNet\UDB3\Deserializer\JSONDeserializer;
use CultuurNet\UDB3\Deserializer\MissingValueException;
use CultuurNet\UDB3\EventExport\EventExportQuery;
use CultuurNet\UDB3\Search\Sorting;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddress;

/**
 * @deprecated
 *   Refactor to implement RequestBodyParser and throw ApiProblemException
 */
abstract class ExportEventsJSONDeserializer extends JSONDeserializer
{
    public function deserialize(string $data): ExportEvents
    {
        $json = parent::deserialize($data);

        if (!isset($json->query)) {
            throw new MissingValueException('query is missing');
        }
        $query = new EventExportQuery($json->query);

        $email = $selection = $include = null;
        // @todo This throws an exception when the e-mail is invalid. How do we handle this?
        if (isset($json->email)) {
            $email = new EmailAddress($json->email);
        }

        if (isset($json->selection)) {
            $selection = $json->selection;
        }

        if (isset($json->include)) {
            $include = $json->include;
        }

        $command = $this->createCommand(
            $query,
            $include,
            $email,
            $selection
        );

        $sorting = Sorting::fromJson($json);

        if ($sorting !== null) {
            $command = $command->withSorting($sorting);
        }

        return $command;
    }

    /**
     * @param string[] $include
     * @param string[]|null $selection
     */
    abstract protected function createCommand(
        EventExportQuery $query,
        $include,
        EmailAddress $address = null,
        $selection = null
    ): ExportEvents;
}
