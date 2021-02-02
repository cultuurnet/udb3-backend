<?php


namespace CultuurNet\UDB3\StringFilter;

/**
 * Interface for filtering the description of a json-ld event.
 */
interface StringFilterInterface
{

    /**
     * @param string $string
     * @return string
     */
    public function filter($string);
}
