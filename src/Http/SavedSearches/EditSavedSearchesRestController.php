<?php

namespace CultuurNet\UDB3\Http\SavedSearches;

use Broadway\CommandHandling\CommandBus;
use CultureFeed_User;
use CultuurNet\UDB3\SavedSearches\Command\SubscribeToSavedSearchJSONDeserializer;
use CultuurNet\UDB3\SavedSearches\Command\UnsubscribeFromSavedSearch;
use CultuurNet\UDB3\HttpFoundation\Response\NoContent;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use ValueObjects\StringLiteral\StringLiteral;

class EditSavedSearchesRestController
{
    /**
     * @var CultureFeed_User
     */
    private $user;

    /**
     * @var CommandBus
     */
    private $commandBus;

    public function __construct(
        CultureFeed_User $user,
        CommandBus $commandBus
    ) {
        $this->user = $user;
        $this->commandBus = $commandBus;
    }

    public function save(Request $request): Response
    {
        $commandDeserializer = new SubscribeToSavedSearchJSONDeserializer(
            new StringLiteral($this->user->id)
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
            new StringLiteral($this->user->id),
            new StringLiteral($id)
        );

        $this->commandBus->dispatch($command);

        return new NoContent();
    }
}
