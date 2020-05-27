<?php
/**
 * @file
 */

namespace CultuurNet\UDB3\Iri;

/**
 * Interface for IRI generators.
 *
 * @link http://en.wikipedia.org/wiki/Dereferenceable_Uniform_Resource_Identifier
 */
interface IriGeneratorInterface
{
    /**
     * Generates a IRI for a specific item.
     *
     * @param string $item
     *  A string uniquely identifying an item.
     *
     * @return string
     *   The IRI for the specified item.
     */
    public function iri($item);
}
