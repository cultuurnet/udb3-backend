<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Event;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\Event\Commands\UpdateFaqs;
use CultuurNet\UDB3\Event\FaqIdDoesNotExist;
use CultuurNet\UDB3\Event\Serializer\FaqsDenormalizer;
use CultuurNet\UDB3\Http\Request\Body\DenormalizingRequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\JsonSchemaLocator;
use CultuurNet\UDB3\Http\Request\Body\JsonSchemaValidatingRequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\RequestBodyParserFactory;
use CultuurNet\UDB3\Http\Request\RouteParameters;
use CultuurNet\UDB3\Http\Response\NoContentResponse;
use CultuurNet\UDB3\Model\ValueObject\Faq\Faqs;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class FaqsRequestHandler implements RequestHandlerInterface
{
    public function __construct(
        private readonly CommandBus $commandBus,
        private readonly DocumentRepository $eventDocumentRepository,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $routeParameters = new RouteParameters($request);
        $eventId = $routeParameters->getEventId();

        $body = json_decode((string) $request->getBody(), true) ?? [];
        $explicitIds = array_values(array_filter(array_column($body, 'id')));

        if ($explicitIds !== []) {
            $document = $this->eventDocumentRepository->fetch($eventId);
            $currentIds = array_column($document->getAssocBody()['faqs'] ?? [], 'id');
            foreach ($explicitIds as $id) {
                if (!in_array($id, $currentIds, true)) {
                    throw new FaqIdDoesNotExist($id);
                }
            }
        }

        $parser = RequestBodyParserFactory::createBaseParser(
            new JsonSchemaValidatingRequestBodyParser(JsonSchemaLocator::EVENT_FAQS_PUT),
            new DenormalizingRequestBodyParser(new FaqsDenormalizer(), Faqs::class)
        );

        /** @var Faqs $faqs */
        $faqs = $parser->parse($request)->getParsedBody();

        $this->commandBus->dispatch(new UpdateFaqs($eventId, $faqs));

        return new NoContentResponse();
    }
}
