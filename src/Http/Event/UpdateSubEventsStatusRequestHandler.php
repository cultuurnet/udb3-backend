<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Event;

use Broadway\CommandHandling\CommandBusInterface;
use CultuurNet\Deserializer\DataValidationException;
use CultuurNet\UDB3\Event\Commands\UpdateSubEventsStatus;
use CultuurNet\UDB3\Event\ValueObjects\Status;
use CultuurNet\UDB3\Event\ValueObjects\StatusReason;
use CultuurNet\UDB3\Event\ValueObjects\StatusType;
use CultuurNet\UDB3\Language;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Request;

class UpdateSubEventsStatusRequestHandler
{
    /**
     * @var CommandBusInterface
     */
    private $commandBus;

    /**
     * @var UpdateSubEventsStatusValidator
     */
    private $validator;

    public function __construct(
        CommandBusInterface $commandBus,
        UpdateSubEventsStatusValidator $validator
    ) {
        $this->commandBus = $commandBus;
        $this->validator = $validator;
    }

    public function __invoke(Request $request, string $eventId)
    {
        $data = json_decode($request->getContent(), true);

        $this->validator->validate($data);

        $command = new UpdateSubEventsStatus($eventId);

        foreach ($data as $subEventStatus) {
            $command = $command->withUpdatedStatus(
                $subEventStatus['id'],
                new Status(
                    StatusType::fromNative($data['status']),
                    $this->parseReason($data)
                )
            );
        }

        $this->commandBus->dispatch($command);
    }

    /**
     * @param array $data
     * @return StatusReason[]
     */
    private function parseReason(array $data): array
    {
        if (!isset($data['reason'])) {
            return  [];
        }

        $reason = [];
        foreach ($reason as $language => $translatedReason) {
            $reason[] = new StatusReason(new Language($language), $translatedReason);
        }

        return $reason;
    }
}
