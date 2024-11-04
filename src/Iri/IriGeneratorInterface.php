<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Iri;

/**
 * Interface for IRI generators.
 *
 * @link http://en.wikipedia.org/wiki/Dereferenceable_Uniform_Resource_Identifier
 */
interface IriGeneratorInterface
{
    public function iri(string $item): string;
}
