<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\RDF;

final class TurtleResponseFactory
{
    private JsonToTurtleConverter $jsonToTurtleConverter;

    public function __construct(JsonToTurtleConverter $jsonToTurtleConverter)
    {
        $this->jsonToTurtleConverter = $jsonToTurtleConverter;
    }

    public function turtle(string $id): TurtleResponse
    {
        $turtle = $this->jsonToTurtleConverter->convert($id);
        return new TurtleResponse($turtle);
    }
}
