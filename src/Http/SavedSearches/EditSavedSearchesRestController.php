<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\SavedSearches;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\SavedSearches\Command\SubscribeToSavedSearchJSONDeserializer;
use CultuurNet\UDB3\SavedSearches\Command\UnsubscribeFromSavedSearch;
use CultuurNet\UDB3\HttpFoundation\Response\NoContent;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use ValueObjects\StringLiteral\StringLiteral;

class EditSavedSearchesRestController
{
    /**
     * @var string
     */
    private $userId;

    /**
     * @var CommandBus
     */
    private $commandBus;

    public function __construct(
        string $userId,
        CommandBus $commandBus
    ) {
        $this->userId = $userId;
        $this->commandBus = $commandBus;
    }

    public function save(Request $request): Response
    {
        $commandDeserializer = new SubscribeToSavedSearchJSONDeserializer(
            new StringLiteral($this->userId)
        );

        $command = $commandDeserializer->deserialize(
            new StringLiteral($request->getContent())
        );

        $this->commandBus->dispatch($command);

        return new Response('', 201);
    }

    public function delete(string $id): Response
    {
        $command = new UnsubscribeFromSavedSearch(
            new StringLiteral($this->userId),
            new StringLiteral($id)
        );

        $this->commandBus->dispatch($command);

        return new NoContent();
    }
}
