<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Event;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\Event\Commands\CreateFaqItem;
use CultuurNet\UDB3\Event\Commands\DeleteFaqItem;
use CultuurNet\UDB3\Event\Commands\UpdateFaqItem;
use CultuurNet\UDB3\Event\Serializer\FaqItemsDenormalizer;
use CultuurNet\UDB3\Http\Request\Body\DenormalizingRequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\JsonSchemaLocator;
use CultuurNet\UDB3\Http\Request\Body\JsonSchemaValidatingRequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\RequestBodyParserFactory;
use CultuurNet\UDB3\Http\Request\RouteParameters;
use CultuurNet\UDB3\Http\Response\NoContentResponse;
use CultuurNet\UDB3\Model\ValueObject\Faq\FaqItems;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class FaqRequestHandler implements RequestHandlerInterface
{
    public function __construct(
        private readonly CommandBus $commandBus,
        private readonly DocumentRepository $eventDocumentRepository
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $routeParameters = new RouteParameters($request);
        $eventId = $routeParameters->getEventId();

        $parser = RequestBodyParserFactory::createBaseParser(
            new JsonSchemaValidatingRequestBodyParser(JsonSchemaLocator::EVENT_FAQ_PUT),
            new DenormalizingRequestBodyParser(new FaqItemsDenormalizer(), FaqItems::class)
        );

        /** @var FaqItems $faqItems */
        $faqItems = $parser->parse($request)->getParsedBody();
        $commands = $this->compareFaqItems($eventId, $faqItems);

        foreach ($commands as $command) {
            $this->commandBus->dispatch($command);
        }

        return new NoContentResponse();
    }

    private function compareFaqItems(string $eventId, FaqItems $incomingItems): array
    {
        $body = $this->eventDocumentRepository->fetch($eventId)->getBody();
        $existingFaqIds = array_map(
            fn (object $item) => $item->id,
            (array)($body->faq ?? [])
        );

        $commands = [];
        $incomingById = $incomingItems->toArray();

        foreach ($incomingById as $id => $translatedFaqItem) {
            if (!in_array($id, $existingFaqIds, true)) {
                $commands[] = new CreateFaqItem($eventId, $translatedFaqItem);
            } else {
                $commands[] = new UpdateFaqItem($eventId, $translatedFaqItem);
            }
        }

        foreach ($existingFaqIds as $existingId) {
            if (!isset($incomingById[$existingId])) {
                $commands[] = new DeleteFaqItem($eventId, $existingId);
            }
        }

        return $commands;
    }
}
