<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Event;

use CultuurNet\UDB3\Event\ReadModel\OSLO\EventOSLOProjector;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\Request\RouteParameters;
use CultuurNet\UDB3\Http\Response\JsonLdResponse;
use CultuurNet\UDB3\Http\Response\TurtleResponse;
use CultuurNet\UDB3\ReadModel\DocumentDoesNotExist;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use EasyRdf\Graph;
use EasyRdf\Parser\JsonLd;
use EasyRdf\RdfNamespace;
use EasyRdf\Serialiser\Turtle;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class GetOsloDetailRequestHandler implements RequestHandlerInterface
{
    private DocumentRepository $documentRepository;

    public function __construct(DocumentRepository $documentRepository)
    {
        $this->documentRepository = $documentRepository;
    }

    /**
     * @throws ApiProblem
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $routeParameters = new RouteParameters($request);
        $eventId = $routeParameters->getEventId();
        $accept = $request->getHeader('accept');

        try {
            $jsonDocument = $this->documentRepository->fetch($eventId);
        } catch (DocumentDoesNotExist $e) {
            throw ApiProblem::urlNotFound('No OSLO representation found for event with id ' . $eventId);
        }

        /**
         * @todo
         *  Remove Turtle support if not needed (useful for debugging now), OR make the EventOSLOProjector save the
         *  Turtle representation in a second repository and just fetch it here, instead of converting the JSON-LD to
         *  Turtle (which requires some knowledge about the graph like the base URI and the prefix namespaces).
         *  Also improve the content-negotiation check to be more compliant with
         *  https://www.rfc-editor.org/rfc/rfc9110.html#section-12.4
         */
        if (count($accept) > 0 && $accept[0] === 'text/turtle') {
            RdfNamespace::set('activiteit', 'https://data.vlaanderen.be/ns/cultuurparticipatie#Activiteit.');

            $parser = new JsonLd();
            $graph = new Graph(EventOSLOProjector::BASE_URI);
            $parser->parse($graph, $jsonDocument->getRawBody(), 'jsonld', EventOSLOProjector::BASE_URI);

            $serializer = new Turtle();
            $turtle = $serializer->serialise($graph, 'turtle');
            return new TurtleResponse($turtle);
        }

        return new JsonLdResponse($jsonDocument->getAssocBody());
    }
}
