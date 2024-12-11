<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Label;

use Broadway\CommandHandling\CommandBus;
use Broadway\UuidGenerator\UuidGeneratorInterface;
use CultuurNet\UDB3\Http\Response\JsonResponse;
use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\Label\Commands\Create;
use CultuurNet\UDB3\Label\ValueObjects\Privacy;
use CultuurNet\UDB3\Label\ValueObjects\Visibility;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\LabelName;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class CreateLabelRequestHandler implements RequestHandlerInterface
{
    private CommandBus $commandBus;

    private UuidGeneratorInterface $uuidGenerator;

    public function __construct(CommandBus $commandBus, UuidGeneratorInterface $uuidGenerator)
    {
        $this->commandBus = $commandBus;
        $this->uuidGenerator = $uuidGenerator;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $body = Json::decodeAssociatively($request->getBody()->getContents());

        $uuid = new Uuid($this->uuidGenerator->generate());

        $this->commandBus->dispatch(
            new Create(
                $uuid,
                new LabelName($body['name']),
                new Visibility($body['visibility']),
                new Privacy($body['privacy'])
            )
        );

        return new JsonResponse(['uuid' => $uuid->toString()]);
    }
}
