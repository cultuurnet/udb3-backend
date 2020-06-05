<?php

namespace CultuurNet\UDB3\Http\Productions;

use Broadway\CommandHandling\CommandBusInterface;
use CultuurNet\UDB3\Event\Productions\GroupEventsAsProduction;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ProductionsWriteController
{
    /**
     * @var CommandBusInterface
     */
    private $commandBus;

    public function __construct(CommandBusInterface $commandBus)
    {
        $this->commandBus = $commandBus;
    }

    public function create(Request $request): Response
    {
        $data = json_decode($request->getContent(), true);

        $command = new GroupEventsAsProduction(
            $data['eventIds'],
            $data['name']
        );

        $this->commandBus->dispatch($command);

        return new Response('', 201);
    }
}
