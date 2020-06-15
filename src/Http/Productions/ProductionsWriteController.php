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

    /**
     * @var CreateProductionValidator
     */
    private $validator;

    public function __construct(
        CommandBusInterface $commandBus,
        CreateProductionValidator $validator
    ) {
        $this->commandBus = $commandBus;
        $this->validator = $validator;
    }

    public function create(Request $request): Response
    {
        $data = json_decode($request->getContent(), true);

        $this->validator->validate($data);

        $command = new GroupEventsAsProduction(
            $data['eventIds'],
            $data['name']
        );

        $this->commandBus->dispatch($command);

        return new Response('', 201);
    }
}
