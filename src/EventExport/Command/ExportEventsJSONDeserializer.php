<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventExport\Command;

use CultuurNet\UDB3\Deserializer\JSONDeserializer;
use CultuurNet\UDB3\Deserializer\MissingValueException;
use CultuurNet\UDB3\EventExport\EventExportQuery;
use CultuurNet\UDB3\EventExport\SortOrder;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddress;

/**
 * @deprecated
 *   Refactor to implement RequestBodyParser and throw ApiProblemException
 */
abstract class ExportEventsJSONDeserializer extends JSONDeserializer
{
    public function deserialize(string $data): ExportEvents
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

        $command = $this->createCommand(
            $query,
            $include,
            $email,
            $selection
        );

        $hasProperty = isset($data->order->property);
        $hasOrder = isset($data->order->order);

        if ($hasProperty && !$hasOrder) {
            throw new MissingValueException("order is incomplete. You should provide a 'order' key.");
        }

        if (!$hasProperty && $hasOrder) {
            throw new MissingValueException("order is incomplete. You should provide a 'property' key.");
        }

        if ($hasProperty && $hasOrder) { // @phpstan-ignore-line
            $sortOrder = new SortOrder(
                $data->order->property,
                $data->order->order,
            );
            $command = $command->withSortOrder($sortOrder);
        }

        return $command;
    }

    /**
     * @param string[] $include
     * @param string[]|null $selection
     * @return ExportEvents
     */
    abstract protected function createCommand(
        EventExportQuery $query,
        $include,
        EmailAddress $address = null,
        $selection = null
    );
}
