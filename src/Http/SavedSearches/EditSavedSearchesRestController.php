<?php

namespace CultuurNet\UDB3\Http\SavedSearches;

use Broadway\CommandHandling\CommandBusInterface;
use CultuurNet\UDB3\SavedSearches\Command\SubscribeToSavedSearchJSONDeserializer;
use CultuurNet\UDB3\SavedSearches\Command\UnsubscribeFromSavedSearch;
use CultuurNet\UDB3\Http\HttpFoundation\NoContent;
use CultuurNet\UDB3\ValueObject\SapiVersion;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use ValueObjects\StringLiteral\StringLiteral;

class EditSavedSearchesRestController
{
    /**
     * @var \CultureFeed_User
     */
    private $user;

    /**
     * @var CommandBusInterface
     */
    private $commandBus;

    /**
     * @param \CultureFeed_User $user
     * @param CommandBusInterface $commandBus
     */
    public function __construct(
        \CultureFeed_User $user,
        CommandBusInterface $commandBus
    ) {
        $this->user = $user;
        $this->commandBus = $commandBus;
    }

    public function save(Request $request, string $sapiVersion): Response
    {
        $commandDeserializer = new SubscribeToSavedSearchJSONDeserializer(
            SapiVersion::fromNative($sapiVersion),
            new StringLiteral($this->user->id)
        );

        $command = $commandDeserializer->deserialize(
            new StringLiteral($request->getContent())
        );

        $this->commandBus->dispatch($command);

        return new Response('', 201);
    }

    public function delete(string $sapiVersion, string $id): Response
    {
        $command = new UnsubscribeFromSavedSearch(
            SapiVersion::fromNative($sapiVersion),
            new StringLiteral($this->user->id),
            new StringLiteral($id)
        );

        $this->commandBus->dispatch($command);

        return new NoContent();
    }
}
