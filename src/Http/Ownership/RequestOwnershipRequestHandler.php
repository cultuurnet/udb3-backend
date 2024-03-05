<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Ownership;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\Http\Request\Body\DenormalizingRequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\RequestBodyParserFactory;
use CultuurNet\UDB3\Http\Response\JsonResponse;
use CultuurNet\UDB3\Ownership\Commands\RequestOwnership;
use CultuurNet\UDB3\Ownership\Serializers\RequestOwnershipDenormalizer;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Ramsey\Uuid\UuidFactoryInterface;

final class RequestOwnershipRequestHandler implements RequestHandlerInterface
{
    private CommandBus $commandBus;
    private UuidFactoryInterface $uuidFactory;

    public function __construct(CommandBus $commandBus, UuidFactoryInterface $uuidFactory)
    {
        $this->commandBus = $commandBus;
        $this->uuidFactory = $uuidFactory;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $requestBodyParser = RequestBodyParserFactory::createBaseParser(
            new DenormalizingRequestBodyParser(
                new RequestOwnershipDenormalizer($this->uuidFactory),
                RequestOwnership::class
            )
        );

        /** @var RequestOwnership $requestOwnership */
        $requestOwnership = $requestBodyParser->parse($request)->getParsedBody();

        $this->commandBus->dispatch($requestOwnership);

        return new JsonResponse(
            [
                'id' => $requestOwnership->getId()->toString(),
            ],
            StatusCodeInterface::STATUS_CREATED
        );
    }
}
