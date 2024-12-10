<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Label;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\Http\Request\RouteParameters;
use CultuurNet\UDB3\Http\Response\NoContentResponse;
use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\Label\Commands\ExcludeLabel;
use CultuurNet\UDB3\Label\Commands\IncludeLabel;
use CultuurNet\UDB3\Label\Commands\MakeInvisible;
use CultuurNet\UDB3\Label\Commands\MakePrivate;
use CultuurNet\UDB3\Label\Commands\MakePublic;
use CultuurNet\UDB3\Label\Commands\MakeVisible;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class PatchLabelRequestHandler implements RequestHandlerInterface
{
    private CommandBus $commandBus;

    public function __construct(CommandBus $commandBus)
    {
        $this->commandBus = $commandBus;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $labelId = new UUID((new RouteParameters($request))->getLabelId());

        $body = Json::decodeAssociatively($request->getBody()->getContents());
        $commandType = new CommandType($body['command']);

        switch ($commandType) {
            case CommandType::makeVisible():
                $this->commandBus->dispatch(new MakeVisible($labelId));
                break;
            case CommandType::makeInvisible():
                $this->commandBus->dispatch(new MakeInvisible($labelId));
                break;
            case CommandType::makePublic():
                $this->commandBus->dispatch(new MakePublic($labelId));
                break;
            case CommandType::makePrivate():
                $this->commandBus->dispatch(new MakePrivate($labelId));
                break;
            case CommandType::include():
                $this->commandBus->dispatch(new IncludeLabel($labelId));
                break;
            case CommandType::exclude():
                $this->commandBus->dispatch(new ExcludeLabel($labelId));
                break;
        }

        return new NoContentResponse();
    }
}
