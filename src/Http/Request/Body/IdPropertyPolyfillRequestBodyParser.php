<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Request\Body;

use CultuurNet\UDB3\Iri\IriGeneratorInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Polyfills or corrects the @id property on incoming JSON-LD of events, places and organizers that need to be imported.
 */
final class IdPropertyPolyfillRequestBodyParser implements RequestBodyParser
{
    private string $iri;

    public function __construct(IriGeneratorInterface $iriGenerator, string $id)
    {
        $this->iri = $iriGenerator->iri($id);
    }

    public function parse(ServerRequestInterface $request): ServerRequestInterface
    {
        $data = $request->getParsedBody();

        if (is_array($data)) {
            $data['@id'] = $this->iri;
        }
        if (is_object($data)) {
            $data->{'@id'} = $this->iri;
        }

        return $request->withParsedBody($data);
    }
}
