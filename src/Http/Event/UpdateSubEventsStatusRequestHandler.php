<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Event;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\Event\Commands\UpdateSubEventsStatus;
use CultuurNet\UDB3\Event\ValueObjects\Status;
use CultuurNet\UDB3\Event\ValueObjects\StatusReason;
use CultuurNet\UDB3\Event\ValueObjects\StatusType;
use CultuurNet\UDB3\HttpFoundation\Response\NoContent;
use CultuurNet\UDB3\Language;
use Symfony\Component\HttpFoundation\Request;

class UpdateSubEventsStatusRequestHandler
{
    /**
     * @var CommandBus
     */
    private $commandBus;

    /**
     * @var UpdateSubEventsStatusValidator
     */
    private $validator;

    public function __construct(
        CommandBus $commandBus,
        UpdateSubEventsStatusValidator $validator
    ) {
        $this->commandBus = $commandBus;
        $this->validator = $validator;
    }

    public function handle(Request $request, string $eventId)
    {
        $data = json_decode($request->getContent(), true);

        $this->validator->validate($data);

        $command = new UpdateSubEventsStatus($eventId);

        foreach ($data as $subEventStatus) {
            $command = $command->withUpdatedStatus(
                $subEventStatus['id'],
                new Status(
                    StatusType::fromNative($subEventStatus['status']['type']),
                    $this->parseReason($subEventStatus)
                )
            );
        }

        $this->commandBus->dispatch($command);

        return new NoContent();
    }

    /**
     * @param array $data
     * @return StatusReason[]
     */
    private function parseReason(array $data): array
    {
        if (!isset($data['status']['reason'])) {
            return [];
        }

        $reason = [];
        foreach ($data['status']['reason'] as $language => $translatedReason) {
            $reason[] = new StatusReason(new Language($language), $translatedReason);
        }

        return $reason;
    }
}
