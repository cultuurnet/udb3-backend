<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\Http\Request\RequestHandler;
use CultuurNet\UDB3\Http\Request\RouteParameters;
use CultuurNet\UDB3\Http\Response\NoContentResponse;
use CultuurNet\UDB3\Offer\Commands\Status\UpdateStatus;
use CultuurNet\UDB3\Event\ValueObjects\Status;
use CultuurNet\UDB3\Event\ValueObjects\StatusReason;
use CultuurNet\UDB3\Event\ValueObjects\StatusType;
use CultuurNet\UDB3\Language;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class UpdateStatusRequestHandler implements RequestHandler
{
    /**
     * @var CommandBus
     */
    private $commandBus;

    /**
     * @var UpdateStatusValidator
     */
    private $validator;

    public function __construct(CommandBus $commandBus, UpdateStatusValidator $validator)
    {
        $this->commandBus = $commandBus;
        $this->validator = $validator;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $routeParameters = new RouteParameters($request);
        $offerId = $routeParameters->get('offerId');

        $data = json_decode((string) $request->getBody(), true);

        $this->validator->validate($data);

        $newStatus = new Status(
            StatusType::fromNative($data['type']),
            $this->parseReason($data)
        );

        $this->commandBus->dispatch(new UpdateStatus($offerId, $newStatus));

        return new NoContentResponse();
    }

    /**
     * @return StatusReason[]
     */
    private function parseReason(array $data): array
    {
        if (!isset($data['reason'])) {
            return [];
        }

        $reason = [];
        foreach ($data['reason'] as $language => $translatedReason) {
            $reason[] = new StatusReason(new Language($language), $translatedReason);
        }

        return $reason;
    }
}
