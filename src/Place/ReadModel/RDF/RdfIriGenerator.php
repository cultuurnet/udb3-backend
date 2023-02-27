<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place\ReadModel\RDF;

use CultuurNet\UDB3\Iri\IriGeneratorInterface;

final class RdfIriGenerator implements IriGeneratorInterface
{
    public function iri($item): string
    {
        return 'https://data.publiq.be/locaties/' . $item;
    }
}
