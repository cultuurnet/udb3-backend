<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\RDF;

use CultuurNet\UDB3\Iri\IriGeneratorInterface;
use CultuurNet\UDB3\RDF\GraphNotFound;
use CultuurNet\UDB3\RDF\GraphRepository;
use EasyRdf\Exception;
use EasyRdf\Serialiser\Turtle;

final class RDFResponseFactory
{
    private GraphRepository $graphRepository;
    private IriGeneratorInterface $iriGenerator;

    public function __construct(GraphRepository $graphRepository, IriGeneratorInterface $iriGenerator)
    {
        $this->graphRepository = $graphRepository;
        $this->iriGenerator = $iriGenerator;
    }

    /**
     * @throws GraphNotFound|Exception
     */
    public function turtle(string $id): TurtleResponse
    {
        $iri = $this->iriGenerator->iri($id);
        $turtle = trim((new Turtle())->serialise($this->graphRepository->get($iri), 'turtle'));

        return new TurtleResponse($turtle);
    }
}
