<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\Http\Request\Body\JsonSchemaLocator;
use CultuurNet\UDB3\Http\Request\Body\JsonSchemaValidatingRequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\RequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\RequestBodyParserFactory;
use CultuurNet\UDB3\Http\Request\RequestHandler;
use CultuurNet\UDB3\Http\Request\RouteParameters;
use CultuurNet\UDB3\Http\Response\NoContentResponse;
use CultuurNet\UDB3\Offer\Commands\Status\UpdateStatus;
use CultuurNet\UDB3\Event\ValueObjects\Status;
use CultuurNet\UDB3\Event\ValueObjects\StatusReason;
use CultuurNet\UDB3\Event\ValueObjects\StatusType;
use CultuurNet\UDB3\Language;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class UpdateStatusRequestHandler implements RequestHandler
{
    private CommandBus $commandBus;
    private RequestBodyParser $parser;

    public function __construct(CommandBus $commandBus)
    {
        $this->commandBus = $commandBus;
        $this->parser = RequestBodyParserFactory::createBaseParser(
            JsonSchemaValidatingRequestBodyParser::fromFile(JsonSchemaLocator::OFFER_STATUS)
        );
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $routeParameters = new RouteParameters($request);
        $offerId = $routeParameters->get('offerId');

        $request = $this->parser->parse($request);
        $data = (object) $request->getParsedBody();

        $newStatus = new Status(
            StatusType::fromNative($data->type),
            $this->parseReason($data)
        );

        $this->commandBus->dispatch(new UpdateStatus($offerId, $newStatus));

        return new NoContentResponse();
    }

    /**
     * @return StatusReason[]
     */
    private function parseReason(object $data): array
    {
        if (!isset($data->reason)) {
            return [];
        }

        $reason = [];
        foreach ($data->reason as $language => $translatedReason) {
            try {
                $language = new Language($language);
            } catch (InvalidArgumentException $e) {
                // Skip unsupported language codes to avoid any extra properties that are passed but not supported from
                // resulting in an error response.
                continue;
            }
            $reason[] = new StatusReason($language, $translatedReason);
        }

        return $reason;
    }
}
