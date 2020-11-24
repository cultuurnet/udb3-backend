<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Event;

use Broadway\CommandHandling\CommandBusInterface;
use CultuurNet\Deserializer\DataValidationException;
use CultuurNet\UDB3\Event\Commands\UpdateSubEventsStatus;
use CultuurNet\UDB3\Event\ValueObjects\EventStatus;
use CultuurNet\UDB3\Event\ValueObjects\EventStatusReason;
use CultuurNet\UDB3\Event\ValueObjects\EventStatusType;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
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

        foreach ($data as $index => $eventStatus) {
            $command = $command->withUpdatedStatus(
                $index,
                new EventStatus(
                    EventStatusType::fromNative($data['status']),
                    $this->parseReason($data)
                )
            );
        }

        $this->commandBus->dispatch($command);
    }

    /**
     * @param array $data
     * @return EventStatusReason[]
     */
    private function parseReason(array $data): array
    {
        if (!isset($data['reason'])) {
            return  [];
        }

        $reason = [];
        foreach ($reason as $language => $translatedReason) {
            $reason[] = new EventStatusReason(new Language($language), $translatedReason);
        }

        return $reason;
    }
}
